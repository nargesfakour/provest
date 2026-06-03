<?php

declare(strict_types=1);

namespace App\Modules\Wallet\Services;

use App\Modules\AbanTether\Exceptions\AbanTetherException;
use App\Modules\AbanTether\Services\AbanTetherServiceInterface;
use App\Modules\Shared\Exceptions\BusinessException;
use App\Modules\Wallet\Enums\WalletTxType;
use App\Modules\Wallet\Enums\WithdrawalStatus;
use App\Modules\Wallet\Models\Withdrawal;
use App\Modules\Wallet\Repositories\WithdrawalRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WithdrawalService implements WithdrawalServiceInterface
{
    public function __construct(
        private readonly WithdrawalRepositoryInterface $withdrawalRepo,
        private readonly WalletServiceInterface        $walletService,
        private readonly AbanTetherServiceInterface    $abanTether,
    ) {}

    public function requestWithdrawal(
        int     $userId,
        string  $amount,
        string  $network,
        string  $destinationAddress,
        ?string $memo,
    ): Withdrawal {
        $fee         = (string) config('wallet.withdrawal_fee', '0.00000000');
        $minAmount   = (string) config('wallet.min_withdrawal',  '10.00000000');
        $totalToLock = bcadd($amount, $fee, 8);

        if (bccomp($amount, $minAmount, 8) < 0) {
            throw new BusinessException(
                "Minimum withdrawal amount is {$minAmount} USDT",
                422
            );
        }

        // Step 1: atomically create the withdrawal record and lock funds.
        // The lock() call uses SELECT FOR UPDATE on the user row and will throw
        // BusinessException(422) if the balance is insufficient.
        $withdrawal = DB::transaction(function () use (
            $userId, $amount, $fee, $totalToLock, $network, $destinationAddress, $memo
        ) {
            $withdrawal = $this->withdrawalRepo->create([
                'user_id'             => $userId,
                'amount'              => $amount,
                'fee'                 => $fee,
                'coin'                => 'USDT',
                'network'             => $network,
                'destination_address' => $destinationAddress,
                'memo'                => $memo,
                'status'              => WithdrawalStatus::Pending->value,
            ]);

            $this->walletService->lock(
                userId:         $userId,
                amount:         $totalToLock,
                referenceType:  'withdrawals',
                referenceId:    $withdrawal->id,
                idempotencyKey: "withdrawal-lock:{$withdrawal->ulid}",
            );

            return $withdrawal;
        });

        // Step 2: call AbanTether outside the DB transaction so we never hold a
        // DB lock while waiting on an external HTTP call.
        try {
            $response = $this->abanTether->requestWithdrawal(
                network:     $network,
                amount:      $amount,
                destination: $destinationAddress,
                memo:        $memo,
            );

            $this->withdrawalRepo->update($withdrawal->id, [
                'abantether_id' => $response->abanTetherId,
                'status'        => WithdrawalStatus::Processing->value,
            ]);

            $withdrawal->refresh();
        } catch (AbanTetherException $e) {
            // Funds are locked and the record exists as Pending.
            // The admin can retry via the approve endpoint (Step 13).
            Log::error('AbanTether withdrawal request failed', [
                'withdrawal_id' => $withdrawal->id,
                'error'         => $e->getMessage(),
            ]);
        }

        return $withdrawal;
    }

    public function paginateForUser(int $userId, int $perPage = 20): LengthAwarePaginator
    {
        return $this->withdrawalRepo->paginateForUser($userId, $perPage);
    }

    public function getWithdrawalFee(string $network): array
    {
        $fee    = (string) config('wallet.withdrawal_fee', '0.00000000');
        $minAmt = (string) config('wallet.min_withdrawal',  '10.00000000');

        return [
            'network'    => strtoupper($network),
            'fee'        => $fee,
            'min_amount' => $minAmt,
            'coin'       => 'USDT',
        ];
    }
}
