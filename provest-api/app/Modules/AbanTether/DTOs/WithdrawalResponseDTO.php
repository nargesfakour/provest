<?php

declare(strict_types=1);

namespace App\Modules\AbanTether\DTOs;

use App\Modules\Shared\DTOs\BaseDTO;

readonly class WithdrawalResponseDTO extends BaseDTO
{
    public function __construct(
        public string $abanTetherId,
        public string $status,
    ) {}
}
