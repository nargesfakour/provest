<?php

declare(strict_types=1);

namespace App\Modules\Wallet\Services;

use App\Modules\Shared\Contracts\ServiceInterface;
use App\Modules\Wallet\Models\Withdrawal;
use Illuminate\Pagination\LengthAwarePaginator;

interface WithdrawalServiceInterface extends ServiceInterface
{
    public function requestWithdrawal(
        int     $userId,
        string  $amount,
        string  $network,
        string  $destinationAddress,
        ?string $memo,
    ): Withdrawal;

    public function paginateForUser(int $userId, int $perPage = 20): LengthAwarePaginator;

    public function getWithdrawalFee(string $network): array;
}
