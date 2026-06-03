<?php

declare(strict_types=1);

namespace App\Modules\Markets\Repositories;

use App\Modules\Markets\Models\Category;
use App\Modules\Shared\Contracts\RepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface CategoryRepositoryInterface extends RepositoryInterface
{
    public function paginate(int $perPage = 20): LengthAwarePaginator;

    public function allActive(): Collection;

    public function existsBySlug(string $slug, ?int $excludeId = null): bool;
}
