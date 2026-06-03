<?php

declare(strict_types=1);

namespace App\Modules\Orders\Repositories;

use App\Modules\Orders\Models\Order;
use Illuminate\Pagination\LengthAwarePaginator;

interface OrderRepositoryInterface
{
    public function create(array $data): Order;

    public function update(int $id, array $data): void;

    public function findById(int $id): ?Order;

    public function findByUlid(string $ulid): ?Order;

    public function findByIdempotencyKey(string $key): ?Order;

    public function lockForUpdate(int $id): ?Order;

    public function paginateForUser(int $userId, array $filters, int $perPage): LengthAwarePaginator;

    public function paginateForEvent(int $eventId, int $perPage): LengthAwarePaginator;

    public function getOpenOrdersForEvent(int $eventId): \Illuminate\Database\Eloquent\Collection;
}
