<?php

declare(strict_types=1);

namespace App\Modules\Auth\Repositories;

use App\Modules\Auth\Enums\UserStatus;
use App\Modules\Shared\Contracts\RepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

interface UserRepositoryInterface extends RepositoryInterface
{
    public function paginateWithFilters(array $filters, int $perPage = 20): LengthAwarePaginator;

    public function setStatus(int $id, UserStatus $status): bool;
}
