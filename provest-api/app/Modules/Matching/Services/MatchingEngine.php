<?php

declare(strict_types=1);

namespace App\Modules\Matching\Services;

use App\Modules\Markets\Enums\EventStatus;
use App\Modules\Markets\Repositories\EventRepositoryInterface;
use App\Modules\Orders\Enums\OrderSide;
use App\Modules\Orders\Enums\OrderStatus;
use App\Modules\Orders\Repositories\OrderRepositoryInterface;
use App\Modules\Positions\Repositories\PositionRepositoryInterface;
use App\Modules\Trades\Events\TradeExecuted;
use App\Modules\Trades\Models\Trade;
use App\Modules\Trades\Repositories\TradeRepositoryInterface;
use Illuminate\Support\Facades\DB;

class MatchingEngine implements MatchingEngineInterface
{
    public function __construct(
        private readonly EventRepositoryInterface    $eventRepo,
        private readonly OrderRepositoryInterface    $orderRepo,
        private readonly TradeRepositoryInterface    $tradeRepo,
        private readonly PositionRepositoryInterface $positionRepo,
    ) {}

    public function match(int $eventId): void
    {
        /** @var Trade[] $executed */
        $executed = [];

        DB::transaction(function () use ($eventId, &$executed): void {
            $event = $this->eventRepo->findByIdForUpdate($eventId);

            if ($event === null || $event->status !== EventStatus::Open) {
                return;
            }

            // Load all open/partial orders, locked for update in id order to prevent deadlocks
            $allOrders = $this->orderRepo->getOpenOrdersForEvent($eventId);

            if ($allOrders->isEmpty()) {
                return;
            }

            // Price-time priority: best price first, oldest order (lowest id) first for ties
            $yesOrders = $allOrders
                ->filter(fn ($o) => $o->side === OrderSide::Yes)
                ->sortBy([['price', 'desc'], ['id', 'asc']])
                ->values();

            $noOrders = $allOrders
                ->filter(fn ($o) => $o->side === OrderSide::No)
                ->sortBy([['price', 'desc'], ['id', 'asc']])
                ->values();

            // Track remaining qty in memory; DB writes happen after each match
            $yesRem = [];
            $noRem  = [];

            foreach ($yesOrders as $o) {
                $yesRem[$o->id] = bcsub($o->quantity, $o->filled_quantity, 8);
            }
            foreach ($noOrders as $o) {
                $noRem[$o->id] = bcsub($o->quantity, $o->filled_quantity, 8);
            }

            foreach ($yesOrders as $yesOrder) {
                if (bccomp($yesRem[$yesOrder->id], '0', 8) <= 0) {
                    continue;
                }

                foreach ($noOrders as $noOrder) {
                    if (bccomp($noRem[$noOrder->id], '0', 8) <= 0) {
                        continue;
                    }

                    // Match when YES.price + NO.price >= 1.0000
                    $priceSum = bcadd($yesOrder->price, $noOrder->price, 4);
                    if (bccomp($priceSum, '1.0000', 4) < 0) {
                        continue;
                    }

                    $tradeQty = bccomp($yesRem[$yesOrder->id], $noRem[$noOrder->id], 8) <= 0
                        ? $yesRem[$yesOrder->id]
                        : $noRem[$noOrder->id];

                    $yesFee = bcmul($tradeQty, $event->fee_rate, 8);
                    $noFee  = bcmul($tradeQty, $event->fee_rate, 8);

                    $trade = $this->tradeRepo->create([
                        'event_id'     => $eventId,
                        'yes_order_id' => $yesOrder->id,
                        'no_order_id'  => $noOrder->id,
                        'yes_user_id'  => $yesOrder->user_id,
                        'no_user_id'   => $noOrder->user_id,
                        'price'        => $yesOrder->price,
                        'quantity'     => $tradeQty,
                        'yes_fee'      => $yesFee,
                        'no_fee'       => $noFee,
                    ]);

                    $executed[] = $trade;

                    $yesRem[$yesOrder->id] = bcsub($yesRem[$yesOrder->id], $tradeQty, 8);
                    $noRem[$noOrder->id]   = bcsub($noRem[$noOrder->id], $tradeQty, 8);

                    $newYesFilled = bcsub($yesOrder->quantity, $yesRem[$yesOrder->id], 8);
                    $yesStatus    = bccomp($yesRem[$yesOrder->id], '0', 8) === 0
                        ? OrderStatus::Filled
                        : OrderStatus::Partial;

                    $this->orderRepo->update($yesOrder->id, [
                        'filled_quantity' => $newYesFilled,
                        'status'          => $yesStatus->value,
                    ]);

                    $newNoFilled = bcsub($noOrder->quantity, $noRem[$noOrder->id], 8);
                    $noStatus    = bccomp($noRem[$noOrder->id], '0', 8) === 0
                        ? OrderStatus::Filled
                        : OrderStatus::Partial;

                    $this->orderRepo->update($noOrder->id, [
                        'filled_quantity' => $newNoFilled,
                        'status'          => $noStatus->value,
                    ]);

                    $this->positionRepo->upsert(
                        userId:     $yesOrder->user_id,
                        eventId:    $eventId,
                        side:       OrderSide::Yes->value,
                        addQty:     $tradeQty,
                        tradePrice: $yesOrder->price,
                    );

                    $this->positionRepo->upsert(
                        userId:     $noOrder->user_id,
                        eventId:    $eventId,
                        side:       OrderSide::No->value,
                        addQty:     $tradeQty,
                        tradePrice: $noOrder->price,
                    );

                    if (bccomp($yesRem[$yesOrder->id], '0', 8) <= 0) {
                        break; // YES fully filled — advance to next YES order
                    }
                }
            }
        });

        // Fire broadcast events after the transaction commits — one per trade
        foreach ($executed as $trade) {
            event(new TradeExecuted($trade));
        }
    }
}
