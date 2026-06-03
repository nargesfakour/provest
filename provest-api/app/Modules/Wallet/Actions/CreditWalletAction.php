<?php

declare(strict_types=1);

namespace App\Modules\Wallet\Actions;

use App\Modules\Wallet\Enums\WalletTxType;
use App\Modules\Wallet\Models\WalletTransaction;
use App\Modules\Wallet\Repositories\WalletRepositoryInterface;

class CreditWalletAction
{
    public function __construct(private readonly WalletRepositoryInterface $repo) {}

    public function handle(
        int         $userId,
        string      $amount,
        WalletTxType $type,
        ?string     $referenceType,
        ?int        $referenceId,
        string      $idempotencyKey,
    ): WalletTransaction {
        $user = $this->repo->lockUserForUpdate($userId);

        $existing = $this->repo->findTransactionByIdempotencyKey($idempotencyKey);
        if ($existing !== null) {
            return $existing;
        }

        $newBalance = bcadd($user->balance, $amount, 8);

        $this->repo->updateUserBalance($userId, $newBalance);

        return $this->repo->createTransaction([
            'user_id'         => $userId,
            'amount'          => $amount,
            'type'            => $type->value,
            'reference_type'  => $referenceType,
            'reference_id'    => $referenceId,
            'balance_after'   => $newBalance,
            'idempotency_key' => $idempotencyKey,
        ]);
    }
}
