<?php

declare(strict_types=1);

namespace App\Modules\Markets\Repositories;

use App\Modules\Markets\Models\Event;
use App\Modules\Shared\Contracts\RepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

interface EventRepositoryInterface extends RepositoryInterface
{
    public function findByIdForUpdate(int $id): ?Event;

    public function paginateWithFilters(array $filters, int $perPage = 20): LengthAwarePaginator;
}
