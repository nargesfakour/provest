<?php

declare(strict_types=1);

namespace App\Modules\Admin\Services;

use App\Modules\Shared\Exceptions\BusinessException;
use App\Modules\Wallet\Enums\DepositStatus;
use App\Modules\Wallet\Enums\WalletTxType;
use App\Modules\Wallet\Models\Deposit;
use App\Modules\Wallet\Repositories\DepositRepositoryInterface;
use App\Modules\Wallet\Services\WalletServiceInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class AdminDepositService implements AdminDepositServiceInterface
{
    public function __construct(
        private readonly DepositRepositoryInterface $depositRepo,
        private readonly WalletServiceInterface     $walletService,
    ) {}

    public function list(array $filters, int $perPage = 20): LengthAwarePaginator
    {
        return $this->depositRepo->paginateWithFilters($filters, $perPage);
    }

    public function findByUlid(string $ulid): Deposit
    {
        $deposit = $this->depositRepo->findByUlid($ulid);

        if ($deposit === null) {
            throw new BusinessException('Deposit not found', 404);
        }

        return $deposit;
    }

    public function reject(string $ulid, string $reason): Deposit
    {
        $deposit = $this->findByUlid($ulid);

        if ($deposit->status !== DepositStatus::Pending) {
            throw new BusinessException(
                'Only Pending deposits can be rejected',
                422
            );
        }

        $this->depositRepo->update($deposit->id, [
            'status'          => DepositStatus::Rejected->value,
            'rejected_reason' => $reason,
        ]);

        $deposit->refresh();

        return $deposit;
    }

    public function confirm(string $ulid): Deposit
    {
        $deposit = $this->findByUlid($ulid);

        if ($deposit->status !== DepositStatus::Pending) {
            throw new BusinessException('Only Pending deposits can be confirmed', 422);
        }

        DB::transaction(function () use ($deposit): void {
            $this->depositRepo->update($deposit->id, [
                'status'       => DepositStatus::Confirmed->value,
                'confirmed_at' => now(),
            ]);

            $this->walletService->credit(
                userId:         $deposit->user_id,
                amount:         $deposit->amount,
                type:           WalletTxType::Deposit,
                referenceType:  'deposits',
                referenceId:    $deposit->id,
                idempotencyKey: "deposit-confirm:{$deposit->ulid}",
            );
        });

        $deposit->refresh();

        return $deposit;
    }
}
