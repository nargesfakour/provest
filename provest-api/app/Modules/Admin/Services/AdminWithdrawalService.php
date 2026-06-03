<?php

declare(strict_types=1);

namespace App\Modules\Admin\Services;

use App\Modules\AbanTether\Exceptions\AbanTetherException;
use App\Modules\AbanTether\Services\AbanTetherServiceInterface;
use App\Modules\Shared\Exceptions\BusinessException;
use App\Modules\Wallet\Enums\WalletTxType;
use App\Modules\Wallet\Enums\WithdrawalStatus;
use App\Modules\Wallet\Models\Withdrawal;
use App\Modules\Wallet\Repositories\WithdrawalRepositoryInterface;
use App\Modules\Wallet\Services\WalletServiceInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class AdminWithdrawalService implements AdminWithdrawalServiceInterface
{
    public function __construct(
        private readonly WithdrawalRepositoryInterface $withdrawalRepo,
        private readonly WalletServiceInterface        $walletService,
        private readonly AbanTetherServiceInterface    $abanTether,
    ) {}

    public function list(array $filters, int $perPage = 20): LengthAwarePaginator
    {
        return $this->withdrawalRepo->paginateWithFilters($filters, $perPage);
    }

    public function findByUlid(string $ulid): Withdrawal
    {
        $withdrawal = $this->withdrawalRepo->findByUlid($ulid);

        if ($withdrawal === null) {
            throw new BusinessException('Withdrawal not found', 404);
        }

        return $withdrawal;
    }

    public function approve(string $ulid): Withdrawal
    {
        $withdrawal = $this->findByUlid($ulid);

        if ($withdrawal->status !== WithdrawalStatus::Pending) {
            throw new BusinessException('Only Pending withdrawals can be approved', 422);
        }

        // If abantether_id already exists from a prior attempt, promote directly.
        if ($withdrawal->abantether_id !== null) {
            $this->withdrawalRepo->update($withdrawal->id, [
                'status' => WithdrawalStatus::Processing->value,
            ]);

            $withdrawal->refresh();

            return $withdrawal;
        }

        try {
            $response = $this->abanTether->requestWithdrawal(
                network:     $withdrawal->network,
                amount:      $withdrawal->amount,
                destination: $withdrawal->destination_address,
                memo:        $withdrawal->memo,
            );
        } catch (AbanTetherException $e) {
            throw new BusinessException(
                "AbanTether API error: {$e->getMessage()}",
                422
            );
        }

        $this->withdrawalRepo->update($withdrawal->id, [
            'abantether_id' => $response->abanTetherId,
            'status'        => WithdrawalStatus::Processing->value,
        ]);

        $withdrawal->refresh();

        return $withdrawal;
    }

    public function reject(string $ulid): Withdrawal
    {
        $withdrawal = $this->findByUlid($ulid);

        if ($withdrawal->status !== WithdrawalStatus::Pending) {
            throw new BusinessException('Only Pending withdrawals can be rejected', 422);
        }

        return DB::transaction(function () use ($withdrawal) {
            $totalLocked = bcadd($withdrawal->amount, $withdrawal->fee, 8);

            $this->walletService->unlock(
                userId:         $withdrawal->user_id,
                amount:         $totalLocked,
                referenceType:  'withdrawals',
                referenceId:    $withdrawal->id,
                idempotencyKey: "withdrawal-unlock:{$withdrawal->ulid}",
            );

            $this->withdrawalRepo->update($withdrawal->id, [
                'status' => WithdrawalStatus::Failed->value,
            ]);

            $withdrawal->refresh();

            return $withdrawal;
        });
    }
}
