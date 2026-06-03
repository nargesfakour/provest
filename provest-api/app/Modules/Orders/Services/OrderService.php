<?php

declare(strict_types=1);

namespace App\Modules\Orders\Services;

use App\Modules\Markets\Enums\EventStatus;
use App\Modules\Markets\Repositories\EventRepositoryInterface;
use App\Modules\Matching\Jobs\MatchOrdersJob;
use App\Modules\Orders\Events\OrderCancelled;
use App\Modules\Orders\Events\OrderPlaced;
use App\Modules\Orders\Enums\OrderSide;
use App\Modules\Orders\Enums\OrderStatus;
use App\Modules\Orders\Models\Order;
use App\Modules\Orders\Repositories\OrderRepositoryInterface;
use App\Modules\Shared\Exceptions\BusinessException;
use App\Modules\Wallet\Services\WalletServiceInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class OrderService implements OrderServiceInterface
{
    public function __construct(
        private readonly OrderRepositoryInterface $orderRepo,
        private readonly EventRepositoryInterface $eventRepo,
        private readonly WalletServiceInterface   $walletService,
    ) {}

    public function place(
        int       $userId,
        string    $eventUlid,
        OrderSide $side,
        string    $price,
        string    $quantity,
        string    $idempotencyKey,
    ): Order {
        // Validate price range: 0.0100 – 0.9900
        if (bccomp($price, '0.0100', 4) < 0 || bccomp($price, '0.9900', 4) > 0) {
            throw new BusinessException('Price must be between 0.0100 and 0.9900', 422);
        }

        // Fast idempotency check outside the transaction
        $existing = $this->orderRepo->findByIdempotencyKey($idempotencyKey);
        if ($existing !== null) {
            return $existing;
        }

        $event = $this->eventRepo->findByUlid($eventUlid);
        if ($event === null) {
            throw new BusinessException('Event not found', 404);
        }

        if ($event->status !== EventStatus::Open) {
            throw new BusinessException('Orders can only be placed on Open events', 422);
        }

        $lockAmount = bcmul($price, $quantity, 8);

        $order = DB::transaction(function () use (
            $userId, $event, $side, $price, $quantity, $idempotencyKey, $lockAmount
        ) {
            // Re-check idempotency inside the transaction after acquiring the user lock
            // via walletService.lock() → SELECT FOR UPDATE on users row.
            $existing = $this->orderRepo->findByIdempotencyKey($idempotencyKey);
            if ($existing !== null) {
                return $existing;
            }

            // Create the order first to obtain its ID for the lock reference.
            $order = $this->orderRepo->create([
                'event_id'        => $event->id,
                'user_id'         => $userId,
                'side'            => $side->value,
                'price'           => $price,
                'quantity'        => $quantity,
                'filled_quantity' => '0.00000000',
                'status'          => OrderStatus::Open->value,
                'idempotency_key' => $idempotencyKey,
            ]);

            // Lock funds. Throws BusinessException(422) on insufficient balance.
            // Rolls back the entire transaction (including the order record above).
            $this->walletService->lock(
                userId:         $userId,
                amount:         $lockAmount,
                referenceType:  'orders',
                referenceId:    $order->id,
                idempotencyKey: "order-lock:{$idempotencyKey}",
            );

            return $order;
        });

        MatchOrdersJob::dispatch($event->id, $order->id);

        // Set the relation directly so broadcastOn() doesn't need an extra query
        $order->setRelation('event', $event);
        $order->loadMissing('user');
        event(new OrderPlaced($order));

        return $order;
    }

    public function cancel(int $userId, string $ulid): Order
    {
        $order = $this->orderRepo->findByUlid($ulid);

        if ($order === null) {
            throw new BusinessException('Order not found', 404);
        }

        if ($order->user_id !== $userId) {
            throw new BusinessException('Forbidden', 403);
        }

        if (!in_array($order->status, [OrderStatus::Open, OrderStatus::Partial], true)) {
            throw new BusinessException('Only Open or Partial orders can be cancelled', 422);
        }

        $order = DB::transaction(function () use ($order) {
            $remaining  = bcsub($order->quantity, $order->filled_quantity, 8);
            $unlockAmount = bcmul($order->price, $remaining, 8);

            $this->walletService->unlock(
                userId:         $order->user_id,
                amount:         $unlockAmount,
                referenceType:  'orders',
                referenceId:    $order->id,
                idempotencyKey: "order-unlock:{$order->ulid}",
            );

            $this->orderRepo->update($order->id, [
                'status' => OrderStatus::Cancelled->value,
            ]);

            $order->refresh();

            return $order;
        });

        $order->loadMissing(['event', 'user']);
        event(new OrderCancelled($order));

        return $order;
    }

    public function listForUser(int $userId, array $filters, int $perPage = 20): LengthAwarePaginator
    {
        return $this->orderRepo->paginateForUser($userId, $filters, $perPage);
    }
}
