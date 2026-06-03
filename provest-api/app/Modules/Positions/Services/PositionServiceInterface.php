<?php

declare(strict_types=1);

namespace App\Modules\Positions\Services;

use Illuminate\Pagination\LengthAwarePaginator;

interface PositionServiceInterface
{
    public function paginateForUser(int $userId, int $perPage): LengthAwarePaginator;
}
