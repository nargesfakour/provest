<?php

declare(strict_types=1);

namespace App\Modules\Auth\Repositories;

use App\Modules\Auth\Enums\UserStatus;
use App\Modules\Auth\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

class EloquentUserRepository implements UserRepositoryInterface
{
    public function findById(int $id): ?Model
    {
        return User::find($id);
    }

    public function findByUlid(string $ulid): ?Model
    {
        return User::where('ulid', $ulid)->first();
    }

    public function findByEmail(string $email): ?User
    {
        return User::where('email', $email)->first();
    }

    public function paginate(int $perPage = 20): LengthAwarePaginator
    {
        return User::latest()->paginate($perPage);
    }

    public function paginateWithFilters(array $filters, int $perPage = 20): LengthAwarePaginator
    {
        return User::query()
            ->when(
                isset($filters['status']),
                fn ($q) => $q->where('status', $filters['status'])
            )
            ->when(
                !empty($filters['search']),
                fn ($q) => $q->where(function ($q2) use ($filters) {
                    $q2->where('name', 'ilike', "%{$filters['search']}%")
                       ->orWhere('email', 'ilike', "%{$filters['search']}%");
                })
            )
            ->latest()
            ->paginate($perPage);
    }

    public function create(array $data): Model
    {
        return User::create($data);
    }

    public function update(int $id, array $data): bool
    {
        return (bool) User::where('id', $id)->update($data);
    }

    public function delete(int $id): bool
    {
        return (bool) User::destroy($id);
    }

    public function setStatus(int $id, UserStatus $status): bool
    {
        return (bool) User::where('id', $id)->update(['status' => $status]);
    }
}
