<?php

declare(strict_types=1);

namespace App\Modules\Markets\Repositories;

use App\Modules\Markets\Models\Category;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class EloquentCategoryRepository implements CategoryRepositoryInterface
{
    public function findById(int $id): ?Model
    {
        return Category::find($id);
    }

    public function findByUlid(string $ulid): ?Model
    {
        return Category::where('ulid', $ulid)->first();
    }

    public function paginate(int $perPage = 20): LengthAwarePaginator
    {
        return Category::latest()->paginate($perPage);
    }

    public function allActive(): Collection
    {
        return Category::with(['subCategories' => fn ($q) => $q->where('is_active', true)->orderBy('name')])
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    public function create(array $data): Model
    {
        return Category::create($data);
    }

    public function update(int $id, array $data): bool
    {
        return (bool) Category::where('id', $id)->update($data);
    }

    public function delete(int $id): bool
    {
        return (bool) Category::destroy($id);
    }

    public function existsBySlug(string $slug, ?int $excludeId = null): bool
    {
        return Category::where('slug', $slug)
            ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))
            ->exists();
    }
}
