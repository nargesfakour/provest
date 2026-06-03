<?php

declare(strict_types=1);

namespace App\Modules\Markets\Services;

use App\Modules\Markets\DTOs\CreateCategoryDTO;
use App\Modules\Markets\DTOs\UpdateCategoryDTO;
use App\Modules\Markets\Models\Category;
use App\Modules\Shared\Contracts\ServiceInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface CategoryServiceInterface extends ServiceInterface
{
    public function list(int $perPage = 20): LengthAwarePaginator;

    public function listActive(): Collection;

    public function create(CreateCategoryDTO $dto): Category;

    public function findByUlid(string $ulid): Category;

    public function update(string $ulid, UpdateCategoryDTO $dto): Category;

    public function delete(string $ulid): void;
}
