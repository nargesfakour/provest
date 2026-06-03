<?php

declare(strict_types=1);

namespace App\Modules\Orders\Services;

use App\Modules\Orders\Enums\OrderSide;
use App\Modules\Orders\Models\Order;
use App\Modules\Shared\Contracts\ServiceInterface;
use Illuminate\Pagination\LengthAwarePaginator;

interface OrderServiceInterface extends ServiceInterface
{
    public function place(
        int       $userId,
        string    $eventUlid,
        OrderSide $side,
        string    $price,
        string    $quantity,
        string    $idempotencyKey,
    ): Order;

    public function cancel(int $userId, string $ulid): Order;

    public function listForUser(int $userId, array $filters, int $perPage = 20): LengthAwarePaginator;
}
