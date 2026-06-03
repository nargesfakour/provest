<?php

declare(strict_types=1);

namespace App\Modules\Admin\Services;

use App\Modules\Shared\Contracts\ServiceInterface;
use App\Modules\Wallet\Models\Withdrawal;
use Illuminate\Pagination\LengthAwarePaginator;

interface AdminWithdrawalServiceInterface extends ServiceInterface
{
    public function list(array $filters, int $perPage = 20): LengthAwarePaginator;

    public function findByUlid(string $ulid): Withdrawal;

    public function approve(string $ulid): Withdrawal;

    public function reject(string $ulid): Withdrawal;
}
