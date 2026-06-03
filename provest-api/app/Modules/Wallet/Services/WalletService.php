<?php

declare(strict_types=1);

namespace App\Modules\Wallet\Services;

use App\Modules\Wallet\Actions\CreditWalletAction;
use App\Modules\Wallet\Actions\DebitWalletAction;
use App\Modules\Wallet\Enums\WalletTxType;
use App\Modules\Wallet\Models\WalletTransaction;
use App\Modules\Wallet\Repositories\WalletRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class WalletService implements WalletServiceInterface
{
    public function __construct(
        private readonly WalletRepositoryInterface $repo,
        private readonly CreditWalletAction        $creditAction,
        private readonly DebitWalletAction         $debitAction,
    ) {}

    public function credit(
        int         $userId,
        string      $amount,
        WalletTxType $type,
        ?string     $referenceType,
        ?int        $referenceId,
        string      $idempotencyKey,
    ): WalletTransaction {
        return DB::transaction(fn () => $this->creditAction->handle(
            userId:         $userId,
            amount:         $amount,
            type:           $type,
            referenceType:  $referenceType,
            referenceId:    $referenceId,
            idempotencyKey: $idempotencyKey,
        ));
    }

    public function debit(
        int         $userId,
        string      $amount,
        WalletTxType $type,
        ?string     $referenceType,
        ?int        $referenceId,
        string      $idempotencyKey,
    ): WalletTransaction {
        return DB::transaction(fn () => $this->debitAction->handle(
            userId:         $userId,
            amount:         $amount,
            type:           $type,
            referenceType:  $referenceType,
            referenceId:    $referenceId,
            idempotencyKey: $idempotencyKey,
        ));
    }

    public function lock(
        int    $userId,
        string $amount,
        string $referenceType,
        int    $referenceId,
        string $idempotencyKey,
    ): WalletTransaction {
        return DB::transaction(fn () => $this->debitAction->handle(
            userId:         $userId,
            amount:         $amount,
            type:           WalletTxType::Lock,
            referenceType:  $referenceType,
            referenceId:    $referenceId,
            idempotencyKey: $idempotencyKey,
        ));
    }

    public function unlock(
        int    $userId,
        string $amount,
        string $referenceType,
        int    $referenceId,
        string $idempotencyKey,
    ): WalletTransaction {
        return DB::transaction(fn () => $this->creditAction->handle(
            userId:         $userId,
            amount:         $amount,
            type:           WalletTxType::Unlock,
            referenceType:  $referenceType,
            referenceId:    $referenceId,
            idempotencyKey: $idempotencyKey,
        ));
    }

    public function getBalance(int $userId): string
    {
        return $this->repo->getBalance($userId);
    }

    public function transactions(int $userId, int $perPage = 20): LengthAwarePaginator
    {
        return $this->repo->paginateTransactionsForUser($userId, $perPage);
    }
}
