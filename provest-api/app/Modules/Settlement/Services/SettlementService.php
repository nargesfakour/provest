<?php

declare(strict_types=1);

namespace App\Modules\Settlement\Services;

use App\Modules\Markets\Enums\EventOutcome;
use App\Modules\Markets\Models\Event;
use App\Modules\Orders\Enums\OrderSide;
use App\Modules\Orders\Enums\OrderStatus;
use App\Modules\Orders\Repositories\OrderRepositoryInterface;
use App\Modules\Positions\Repositories\PositionRepositoryInterface;
use App\Modules\Settlement\Enums\SettleStatus;
use App\Modules\Settlement\Repositories\SettlementRepositoryInterface;
use App\Modules\Settlement\Events\EventSettled;
use App\Modules\Wallet\Enums\WalletTxType;
use App\Modules\Wallet\Services\WalletServiceInterface;
use Illuminate\Support\Facades\DB;

class SettlementService implements SettlementServiceInterface
{
    public function __construct(
        private readonly OrderRepositoryInterface      $orderRepo,
        private readonly PositionRepositoryInterface   $positionRepo,
        private readonly SettlementRepositoryInterface $settlementRepo,
        private readonly WalletServiceInterface        $walletService,
    ) {}

    public function settle(int $eventId, EventOutcome $outcome): void
    {
        $eventUlid = null;

        DB::transaction(function () use ($eventId, $outcome, &$eventUlid): void {
            // Lock the event row — blocks any concurrent settlement attempt for the same event
            /** @var Event|null $event */
            $event = Event::where('id', $eventId)->lockForUpdate()->first();

            if ($event === null) {
                return;
            }

            // Idempotency: skip if any settlement records already exist for this event
            if ($this->settlementRepo->existsForEvent($eventId)) {
                return;
            }

            $eventUlid = $event->ulid;

            // ── Phase 1: Cancel all remaining open / partial orders ──────────
            // getOpenOrdersForEvent uses lockForUpdate, so these rows are safe to modify.
            $openOrders = $this->orderRepo->getOpenOrdersForEvent($eventId);

            foreach ($openOrders as $order) {
                $remaining = bcsub($order->quantity, $order->filled_quantity, 8);

                if (bccomp($remaining, '0', 8) > 0) {
                    $this->walletService->unlock(
                        userId:        $order->user_id,
                        amount:        bcmul($order->price, $remaining, 8),
                        referenceType: 'orders',
                        referenceId:   $order->id,
                        idempotencyKey: "settle-cancel:{$order->ulid}",
                    );
                }

                $this->orderRepo->update($order->id, [
                    'status' => OrderStatus::Cancelled->value,
                ]);
            }

            // ── Phase 2: Pay out winners ─────────────────────────────────────
            $winningSide = $outcome === EventOutcome::Yes ? OrderSide::Yes : OrderSide::No;

            $positions = $this->positionRepo->getForEvent($eventId);

            foreach ($positions as $position) {
                if ($position->side !== $winningSide) {
                    continue;
                }

                // gross = quantity × 1.00 USDT per share
                $gross = bcmul($position->quantity, '1.00000000', 8);
                // fee already determined at trade time: quantity × event.fee_rate
                $fee = bcmul($position->quantity, $event->fee_rate, 8);
                $net = bcsub($gross, $fee, 8);

                if (bccomp($net, '0', 8) <= 0) {
                    continue;
                }

                // Create the settlement record first to obtain its ID as the wallet reference
                $settlement = $this->settlementRepo->create([
                    'event_id' => $eventId,
                    'user_id'  => $position->user_id,
                    'side'     => $winningSide->value,
                    'quantity' => $position->quantity,
                    'payout'   => $net,
                    'status'   => SettleStatus::Paid->value,
                ]);

                $this->walletService->credit(
                    userId:         $position->user_id,
                    amount:         $net,
                    type:           WalletTxType::Win,
                    referenceType:  'settlements',
                    referenceId:    $settlement->id,
                    idempotencyKey: "settle-win:{$eventId}:{$position->user_id}:{$winningSide->value}",
                );
            }
        });

        if ($eventUlid !== null) {
            event(new EventSettled($eventUlid, $outcome));
        }
    }
}
