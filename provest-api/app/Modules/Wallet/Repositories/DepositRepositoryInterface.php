<?php

declare(strict_types=1);

namespace App\Modules\Wallet\Repositories;

use App\Modules\Wallet\Models\Deposit;
use Illuminate\Pagination\LengthAwarePaginator;

interface DepositRepositoryInterface
{
    public function create(array $data): Deposit;

    public function existsByTxId(string $txId): bool;

    public function findByUlid(string $ulid): ?Deposit;

    public function paginateForUser(int $userId, int $perPage): LengthAwarePaginator;

    public function update(int $id, array $data): void;

    public function paginateWithFilters(array $filters, int $perPage): LengthAwarePaginator;
}
