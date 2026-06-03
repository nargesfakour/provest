<?php

declare(strict_types=1);

namespace App\Modules\Wallet\Services;

use App\Modules\AbanTether\Exceptions\AbanTetherException;
use App\Modules\AbanTether\Services\AbanTetherServiceInterface;
use App\Modules\Shared\Exceptions\BusinessException;
use App\Modules\Wallet\Enums\DepositStatus;
use App\Modules\Wallet\Enums\WalletTxType;
use App\Modules\Wallet\Models\Deposit;
use App\Modules\Wallet\Repositories\DepositRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class DepositService implements DepositServiceInterface
{
    public function __construct(
        private readonly DepositRepositoryInterface  $depositRepo,
        private readonly WalletServiceInterface      $walletService,
        private readonly AbanTetherServiceInterface  $abanTether,
    ) {}

    public function processDeposit(int $userId, string $txId, string $network): Deposit
    {
        if ($this->depositRepo->existsByTxId($txId)) {
            throw new BusinessException("Transaction {$txId} has already been processed", 409);
        }

        try {
            $verification = $this->abanTether->verifyDeposit($txId);
        } catch (AbanTetherException $e) {
            throw new BusinessException($e->getMessage(), 422);
        }

        return DB::transaction(function () use ($userId, $txId, $network, $verification) {
            // Re-check inside transaction to handle the race window between the
            // outer check and acquiring the DB lock.
            if ($this->depositRepo->existsByTxId($txId)) {
                throw new BusinessException("Transaction {$txId} has already been processed", 409);
            }

            $deposit = $this->depositRepo->create([
                'user_id'      => $userId,
                'tx_id'        => $txId,
                'amount'       => $verification->amount,
                'coin'         => $verification->coin,
                'network'      => $network,
                'status'       => DepositStatus::Confirmed->value,
                'confirmed_at' => now(),
            ]);

            $this->walletService->credit(
                userId:         $userId,
                amount:         $verification->amount,
                type:           WalletTxType::Deposit,
                referenceType:  'deposits',
                referenceId:    $deposit->id,
                idempotencyKey: "deposit:{$txId}",
            );

            return $deposit;
        });
    }

    public function paginateForUser(int $userId, int $perPage = 20): LengthAwarePaginator
    {
        return $this->depositRepo->paginateForUser($userId, $perPage);
    }
}
