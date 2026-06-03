<?php

declare(strict_types=1);

namespace App\Modules\Admin\Services;

use App\Modules\Shared\Contracts\ServiceInterface;
use App\Modules\Wallet\Models\Deposit;
use Illuminate\Pagination\LengthAwarePaginator;

interface AdminDepositServiceInterface extends ServiceInterface
{
    public function list(array $filters, int $perPage = 20): LengthAwarePaginator;

    public function findByUlid(string $ulid): Deposit;

    public function reject(string $ulid, string $reason): Deposit;

    public function confirm(string $ulid): Deposit;
}
