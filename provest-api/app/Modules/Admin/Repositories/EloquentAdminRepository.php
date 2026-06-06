<?php

declare(strict_types=1);

namespace App\Modules\Admin\Repositories;

use App\Modules\Admin\Models\Admin;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

class EloquentAdminRepository implements AdminRepositoryInterface
{
    public function findById(int $id): ?Model
    {
        return Admin::find($id);
    }

    public function findByUlid(string $ulid): ?Model
    {
        return Admin::where('ulid', $ulid)->first();
    }

    public function paginate(int $perPage = 20): LengthAwarePaginator
    {
        return Admin::latest()->paginate($perPage);
    }

    public function create(array $data): Model
    {
        return Admin::create($data);
    }

    public function update(int $id, array $data): bool
    {
        return (bool) Admin::where('id', $id)->update($data);
    }

    public function delete(int $id): bool
    {
        return (bool) Admin::destroy($id);
    }
}
