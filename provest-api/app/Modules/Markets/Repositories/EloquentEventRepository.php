<?php

declare(strict_types=1);

namespace App\Modules\Markets\Repositories;

use App\Modules\Markets\Models\Event;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

class EloquentEventRepository implements EventRepositoryInterface
{
    public function findById(int $id): ?Model
    {
        return Event::find($id);
    }

    public function findByUlid(string $ulid): ?Model
    {
        return Event::with(['subCategory.category', 'creator'])
            ->where('ulid', $ulid)
            ->first();
    }

    public function paginate(int $perPage = 20): LengthAwarePaginator
    {
        return Event::with(['subCategory.category'])->latest()->paginate($perPage);
    }

    public function paginateWithFilters(array $filters, int $perPage = 20): LengthAwarePaginator
    {
        return Event::with(['subCategory.category'])
            ->when(
                isset($filters['status']),
                fn ($q) => $q->where('status', $filters['status'])
            )
            ->when(
                isset($filters['category']),
                fn ($q) => $q->whereHas(
                    'subCategory.category',
                    fn ($q2) => $q2->where('ulid', $filters['category'])
                )
            )
            ->when(
                isset($filters['sub_category']),
                fn ($q) => $q->whereHas(
                    'subCategory',
                    fn ($q2) => $q2->where('ulid', $filters['sub_category'])
                )
            )
            ->latest()
            ->paginate($perPage);
    }

    public function create(array $data): Model
    {
        return Event::create($data);
    }

    public function update(int $id, array $data): bool
    {
        return (bool) Event::where('id', $id)->update($data);
    }

    public function delete(int $id): bool
    {
        return (bool) Event::destroy($id);
    }
}
