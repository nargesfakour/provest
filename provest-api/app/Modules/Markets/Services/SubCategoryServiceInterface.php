<?php

declare(strict_types=1);

namespace App\Modules\Markets\Services;

use App\Modules\Markets\DTOs\CreateSubCategoryDTO;
use App\Modules\Markets\DTOs\UpdateSubCategoryDTO;
use App\Modules\Markets\Models\SubCategory;
use App\Modules\Shared\Contracts\ServiceInterface;
use Illuminate\Pagination\LengthAwarePaginator;

interface SubCategoryServiceInterface extends ServiceInterface
{
    public function listByCategory(string $categoryUlid, int $perPage = 20): LengthAwarePaginator;

    public function create(CreateSubCategoryDTO $dto): SubCategory;

    public function findByUlid(string $ulid): SubCategory;

    public function update(string $ulid, UpdateSubCategoryDTO $dto): SubCategory;

    public function delete(string $ulid): void;
}
