<?php

declare(strict_types=1);

namespace App\Modules\Wallet\Repositories;

use App\Modules\Auth\Models\User;
use App\Modules\Wallet\Models\WalletTransaction;
use Illuminate\Pagination\LengthAwarePaginator;

interface WalletRepositoryInterface
{
    public function lockUserForUpdate(int $userId): User;

    public function updateUserBalance(int $userId, string $balance): void;

    public function createTransaction(array $data): WalletTransaction;

    public function findTransactionByIdempotencyKey(string $key): ?WalletTransaction;

    public function paginateTransactionsForUser(int $userId, int $perPage): LengthAwarePaginator;

    public function getBalance(int $userId): string;
}
