<?php

declare(strict_types=1);

namespace App\Modules\Markets\Services;

use App\Modules\Markets\DTOs\CreateSubCategoryDTO;
use App\Modules\Markets\DTOs\UpdateSubCategoryDTO;
use App\Modules\Markets\Models\SubCategory;
use App\Modules\Markets\Repositories\SubCategoryRepositoryInterface;
use App\Modules\Shared\Exceptions\BusinessException;
use Illuminate\Pagination\LengthAwarePaginator;

class SubCategoryService implements SubCategoryServiceInterface
{
    public function __construct(
        private readonly SubCategoryRepositoryInterface $repo,
        private readonly CategoryServiceInterface       $categoryService,
    ) {}

    public function listByCategory(string $categoryUlid, int $perPage = 20): LengthAwarePaginator
    {
        $category = $this->categoryService->findByUlid($categoryUlid);

        return $this->repo->paginateByCategory($category->id, $perPage);
    }

    public function create(CreateSubCategoryDTO $dto): SubCategory
    {
        if ($this->repo->existsBySlug($dto->slug)) {
            throw new BusinessException("Slug '{$dto->slug}' is already taken.", 422);
        }

        /** @var SubCategory */
        $subCategory = $this->repo->create([
            'category_id' => $dto->categoryId,
            'name'        => $dto->name,
            'slug'        => $dto->slug,
            'is_active'   => $dto->isActive,
        ]);

        return $subCategory->load('category');
    }

    public function findByUlid(string $ulid): SubCategory
    {
        /** @var SubCategory|null */
        $subCategory = $this->repo->findByUlid($ulid);

        if (!$subCategory) {
            throw new BusinessException('Sub-category not found.', 404);
        }

        return $subCategory->load('category');
    }

    public function update(string $ulid, UpdateSubCategoryDTO $dto): SubCategory
    {
        $subCategory = $this->findByUlid($ulid);

        if ($this->repo->existsBySlug($dto->slug, $subCategory->id)) {
            throw new BusinessException("Slug '{$dto->slug}' is already taken.", 422);
        }

        $this->repo->update($subCategory->id, [
            'name'      => $dto->name,
            'slug'      => $dto->slug,
            'is_active' => $dto->isActive,
        ]);

        return $subCategory->fresh('category');
    }

    public function delete(string $ulid): void
    {
        $subCategory = $this->findByUlid($ulid);

        if ($this->repo->hasEvents($subCategory->id)) {
            throw new BusinessException('Cannot delete a sub-category that has events.', 422);
        }

        $this->repo->delete($subCategory->id);
    }
}
