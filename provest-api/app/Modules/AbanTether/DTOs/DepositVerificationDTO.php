<?php

declare(strict_types=1);

namespace App\Modules\AbanTether\DTOs;

use App\Modules\Shared\DTOs\BaseDTO;

readonly class DepositVerificationDTO extends BaseDTO
{
    public function __construct(
        public string $txId,
        public string $amount,
        public string $coin,
        public string $network,
        public string $state,
    ) {}
}
