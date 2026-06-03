<?php

declare(strict_types=1);

namespace App\Modules\Markets\Repositories;

use App\Modules\Shared\Contracts\RepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

interface SubCategoryRepositoryInterface extends RepositoryInterface
{
    public function paginateByCategory(int $categoryId, int $perPage = 20): LengthAwarePaginator;

    public function existsBySlug(string $slug, ?int $excludeId = null): bool;

    public function hasEvents(int $subCategoryId): bool;
}
