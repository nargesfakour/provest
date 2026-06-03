<?php

declare(strict_types=1);

namespace App\Modules\Wallet\Repositories;

use App\Modules\Wallet\Models\Withdrawal;
use Illuminate\Pagination\LengthAwarePaginator;

interface WithdrawalRepositoryInterface
{
    public function create(array $data): Withdrawal;

    public function update(int $id, array $data): void;

    public function findById(int $id): ?Withdrawal;

    public function findByUlid(string $ulid): ?Withdrawal;

    public function paginateForUser(int $userId, int $perPage): LengthAwarePaginator;

    public function paginateWithFilters(array $filters, int $perPage): LengthAwarePaginator;
}
