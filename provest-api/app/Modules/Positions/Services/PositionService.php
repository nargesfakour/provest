<?php

declare(strict_types=1);

namespace App\Modules\Positions\Services;

use App\Modules\Positions\Repositories\PositionRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

class PositionService implements PositionServiceInterface
{
    public function __construct(
        private readonly PositionRepositoryInterface $repo,
    ) {}

    public function paginateForUser(int $userId, int $perPage): LengthAwarePaginator
    {
        return $this->repo->paginateForUser($userId, $perPage);
    }
}
