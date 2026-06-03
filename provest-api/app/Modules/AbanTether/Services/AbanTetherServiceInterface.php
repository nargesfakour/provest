<?php

declare(strict_types=1);

namespace App\Modules\AbanTether\Services;

use App\Modules\AbanTether\DTOs\DepositVerificationDTO;
use App\Modules\AbanTether\DTOs\WithdrawalResponseDTO;
use App\Modules\Shared\Contracts\ServiceInterface;

interface AbanTetherServiceInterface extends ServiceInterface
{
    public function verifyDeposit(string $txId): DepositVerificationDTO;

    public function getDepositAddress(string $network = 'TRC20'): string;

    public function requestWithdrawal(
        string  $network,
        string  $amount,
        string  $destination,
        ?string $memo,
    ): WithdrawalResponseDTO;
}
