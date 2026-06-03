<?php

declare(strict_types=1);

namespace App\Modules\Trades\Repositories;

use App\Modules\Trades\Models\Trade;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface TradeRepositoryInterface
{
    public function create(array $data): Trade;

    public function paginateForEvent(int $eventId, int $perPage): LengthAwarePaginator;

    public function paginateForUser(int $userId, int $perPage): LengthAwarePaginator;

    public function sumFeesForEvent(int $eventId): array;

    public function sumFeesWithFilters(array $filters): array;

    public function paginateWithFilters(array $filters, int $perPage): LengthAwarePaginator;

    public function dailyVolume(array $filters): Collection;
}
