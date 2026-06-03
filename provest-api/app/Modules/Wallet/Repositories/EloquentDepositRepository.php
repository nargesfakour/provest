<?php

declare(strict_types=1);

namespace App\Modules\Wallet\Repositories;

use App\Modules\Wallet\Models\Deposit;
use Illuminate\Pagination\LengthAwarePaginator;

class EloquentDepositRepository implements DepositRepositoryInterface
{
    public function create(array $data): Deposit
    {
        /** @var Deposit $deposit */
        $deposit = Deposit::create($data);

        return $deposit;
    }

    public function existsByTxId(string $txId): bool
    {
        return Deposit::where('tx_id', $txId)->exists();
    }

    public function findByUlid(string $ulid): ?Deposit
    {
        /** @var Deposit|null $deposit */
        $deposit = Deposit::where('ulid', $ulid)->first();

        return $deposit;
    }

    public function paginateForUser(int $userId, int $perPage): LengthAwarePaginator
    {
        return Deposit::where('user_id', $userId)
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    public function update(int $id, array $data): void
    {
        Deposit::where('id', $id)->update($data);
    }

    public function paginateWithFilters(array $filters, int $perPage): LengthAwarePaginator
    {
        $query = Deposit::with('user')->orderByDesc('id');

        if (isset($filters['status']) && $filters['status'] !== null && $filters['status'] !== '') {
            $query->where('status', $filters['status']);
        }

        return $query->paginate($perPage);
    }
}
