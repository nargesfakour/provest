<?php

declare(strict_types=1);

namespace App\Modules\Wallet\Repositories;

use App\Modules\Auth\Models\User;
use App\Modules\Shared\Exceptions\BusinessException;
use App\Modules\Wallet\Models\WalletTransaction;
use Illuminate\Pagination\LengthAwarePaginator;

class EloquentWalletRepository implements WalletRepositoryInterface
{
    public function lockUserForUpdate(int $userId): User
    {
        /** @var User|null $user */
        $user = User::where('id', $userId)->lockForUpdate()->first();

        if ($user === null) {
            throw new BusinessException("User #{$userId} not found", 404);
        }

        return $user;
    }

    public function updateUserBalance(int $userId, string $balance): void
    {
        User::where('id', $userId)->update(['balance' => $balance]);
    }

    public function createTransaction(array $data): WalletTransaction
    {
        /** @var WalletTransaction $tx */
        $tx = WalletTransaction::create($data);

        return $tx;
    }

    public function findTransactionByIdempotencyKey(string $key): ?WalletTransaction
    {
        /** @var WalletTransaction|null $tx */
        $tx = WalletTransaction::where('idempotency_key', $key)->first();

        return $tx;
    }

    public function paginateTransactionsForUser(int $userId, int $perPage): LengthAwarePaginator
    {
        return WalletTransaction::where('user_id', $userId)
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    public function getBalance(int $userId): string
    {
        return (string) (User::where('id', $userId)->value('balance') ?? '0.00000000');
    }
}
