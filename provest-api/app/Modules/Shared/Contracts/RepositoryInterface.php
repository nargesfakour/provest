<?php

declare(strict_types=1);

namespace App\Modules\Shared\Contracts;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

interface RepositoryInterface
{
    public function findById(int $id): ?Model;

    public function findByUlid(string $ulid): ?Model;

    public function paginate(int $perPage = 20): LengthAwarePaginator;

    public function create(array $data): Model;

    public function update(int $id, array $data): bool;

    public function delete(int $id): bool;
}
