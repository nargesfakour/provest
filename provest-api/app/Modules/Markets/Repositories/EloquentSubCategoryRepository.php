<?php

declare(strict_types=1);

namespace App\Modules\Markets\Repositories;

use App\Modules\Markets\Models\SubCategory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

class EloquentSubCategoryRepository implements SubCategoryRepositoryInterface
{
    public function findById(int $id): ?Model
    {
        return SubCategory::find($id);
    }

    public function findByUlid(string $ulid): ?Model
    {
        return SubCategory::where('ulid', $ulid)->first();
    }

    public function paginate(int $perPage = 20): LengthAwarePaginator
    {
        return SubCategory::latest()->paginate($perPage);
    }

    public function paginateByCategory(int $categoryId, int $perPage = 20): LengthAwarePaginator
    {
        return SubCategory::where('category_id', $categoryId)->latest()->paginate($perPage);
    }

    public function create(array $data): Model
    {
        return SubCategory::create($data);
    }

    public function update(int $id, array $data): bool
    {
        return (bool) SubCategory::where('id', $id)->update($data);
    }

    public function delete(int $id): bool
    {
        return (bool) SubCategory::destroy($id);
    }

    public function existsBySlug(string $slug, ?int $excludeId = null): bool
    {
        return SubCategory::where('slug', $slug)
            ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))
            ->exists();
    }

    public function hasEvents(int $subCategoryId): bool
    {
        return SubCategory::where('id', $subCategoryId)->whereHas('events')->exists();
    }
}
