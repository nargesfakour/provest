<?php

declare(strict_types=1);

namespace App\Modules\Markets\Services;

use App\Modules\Markets\DTOs\CreateCategoryDTO;
use App\Modules\Markets\DTOs\UpdateCategoryDTO;
use App\Modules\Markets\Models\Category;
use App\Modules\Markets\Repositories\CategoryRepositoryInterface;
use App\Modules\Shared\Exceptions\BusinessException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class CategoryService implements CategoryServiceInterface
{
    public function __construct(
        private readonly CategoryRepositoryInterface $repo,
    ) {}

    public function list(int $perPage = 20): LengthAwarePaginator
    {
        return $this->repo->paginate($perPage);
    }

    public function listActive(): Collection
    {
        return $this->repo->allActive();
    }

    public function create(CreateCategoryDTO $dto): Category
    {
        if ($this->repo->existsBySlug($dto->slug)) {
            throw new BusinessException("Slug '{$dto->slug}' is already taken.", 422);
        }

        /** @var Category */
        return $this->repo->create([
            'name'      => $dto->name,
            'slug'      => $dto->slug,
            'is_active' => $dto->isActive,
        ]);
    }

    public function findByUlid(string $ulid): Category
    {
        /** @var Category|null */
        $category = $this->repo->findByUlid($ulid);

        if (!$category) {
            throw new BusinessException('Category not found.', 404);
        }

        return $category;
    }

    public function update(string $ulid, UpdateCategoryDTO $dto): Category
    {
        $category = $this->findByUlid($ulid);

        if ($this->repo->existsBySlug($dto->slug, $category->id)) {
            throw new BusinessException("Slug '{$dto->slug}' is already taken.", 422);
        }

        $this->repo->update($category->id, [
            'name'      => $dto->name,
            'slug'      => $dto->slug,
            'is_active' => $dto->isActive,
        ]);

        return $category->fresh();
    }

    public function delete(string $ulid): void
    {
        $category = $this->findByUlid($ulid);

        $hasEvents = $category->subCategories()->whereHas('events')->exists();

        if ($hasEvents) {
            throw new BusinessException('Cannot delete a category that has events.', 422);
        }

        $this->repo->delete($category->id);
    }
}
