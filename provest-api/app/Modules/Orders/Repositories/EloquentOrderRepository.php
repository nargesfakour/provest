<?php

declare(strict_types=1);

namespace App\Modules\Orders\Repositories;

use App\Modules\Orders\Enums\OrderStatus;
use App\Modules\Orders\Models\Order;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class EloquentOrderRepository implements OrderRepositoryInterface
{
    public function create(array $data): Order
    {
        /** @var Order $order */
        $order = Order::create($data);

        return $order;
    }

    public function update(int $id, array $data): void
    {
        Order::where('id', $id)->update($data);
    }

    public function findById(int $id): ?Order
    {
        /** @var Order|null $order */
        $order = Order::find($id);

        return $order;
    }

    public function findByUlid(string $ulid): ?Order
    {
        /** @var Order|null $order */
        $order = Order::where('ulid', $ulid)->first();

        return $order;
    }

    public function findByIdempotencyKey(string $key): ?Order
    {
        /** @var Order|null $order */
        $order = Order::where('idempotency_key', $key)->first();

        return $order;
    }

    public function lockForUpdate(int $id): ?Order
    {
        /** @var Order|null $order */
        $order = Order::where('id', $id)->lockForUpdate()->first();

        return $order;
    }

    public function paginateForUser(int $userId, array $filters, int $perPage): LengthAwarePaginator
    {
        $query = Order::with('event')
            ->where('user_id', $userId)
            ->orderByDesc('id');

        if (isset($filters['status']) && $filters['status'] !== null && $filters['status'] !== '') {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['event_ulid'])) {
            $query->whereHas('event', fn ($q) => $q->where('ulid', $filters['event_ulid']));
        }

        return $query->paginate($perPage);
    }

    public function paginateForEvent(int $eventId, int $perPage): LengthAwarePaginator
    {
        return Order::with('user')
            ->where('event_id', $eventId)
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    public function getOpenOrdersForEvent(int $eventId): Collection
    {
        return Order::where('event_id', $eventId)
            ->whereIn('status', [OrderStatus::Open->value, OrderStatus::Partial->value])
            ->orderBy('id')
            ->lockForUpdate()
            ->get();
    }
}
