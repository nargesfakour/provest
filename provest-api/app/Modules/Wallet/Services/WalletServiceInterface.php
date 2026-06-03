<?php

declare(strict_types=1);

namespace App\Modules\Wallet\Services;

use App\Modules\Shared\Contracts\ServiceInterface;
use App\Modules\Wallet\Enums\WalletTxType;
use App\Modules\Wallet\Models\WalletTransaction;
use Illuminate\Pagination\LengthAwarePaginator;

interface WalletServiceInterface extends ServiceInterface
{
    public function credit(
        int         $userId,
        string      $amount,
        WalletTxType $type,
        ?string     $referenceType,
        ?int        $referenceId,
        string      $idempotencyKey,
    ): WalletTransaction;

    public function debit(
        int         $userId,
        string      $amount,
        WalletTxType $type,
        ?string     $referenceType,
        ?int        $referenceId,
        string      $idempotencyKey,
    ): WalletTransaction;

    public function lock(
        int    $userId,
        string $amount,
        string $referenceType,
        int    $referenceId,
        string $idempotencyKey,
    ): WalletTransaction;

    public function unlock(
        int    $userId,
        string $amount,
        string $referenceType,
        int    $referenceId,
        string $idempotencyKey,
    ): WalletTransaction;

    public function getBalance(int $userId): string;

    public function transactions(int $userId, int $perPage = 20): LengthAwarePaginator;
}
