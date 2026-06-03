<?php

declare(strict_types=1);

namespace App\Modules\Wallet\Services;

use App\Modules\Shared\Contracts\ServiceInterface;
use App\Modules\Wallet\Models\Deposit;
use Illuminate\Pagination\LengthAwarePaginator;

interface DepositServiceInterface extends ServiceInterface
{
    public function processDeposit(int $userId, string $txId, string $network): Deposit;

    public function paginateForUser(int $userId, int $perPage = 20): LengthAwarePaginator;
}
