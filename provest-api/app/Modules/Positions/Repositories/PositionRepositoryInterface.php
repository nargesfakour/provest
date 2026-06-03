<?php

declare(strict_types=1);

namespace App\Modules\Positions\Repositories;

use App\Modules\Positions\Models\Position;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface PositionRepositoryInterface
{
    public function upsert(int $userId, int $eventId, int $side, string $addQty, string $tradePrice): Position;

    public function paginateForUser(int $userId, int $perPage): LengthAwarePaginator;

    public function getForEvent(int $eventId): Collection;

    public function lockForSettlement(int $userId, int $eventId, int $side): ?Position;
}
