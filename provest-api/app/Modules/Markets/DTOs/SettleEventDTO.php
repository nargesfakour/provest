<?php

declare(strict_types=1);

namespace App\Modules\Markets\DTOs;

use App\Modules\Markets\Enums\EventOutcome;
use App\Modules\Shared\DTOs\BaseDTO;

readonly class SettleEventDTO extends BaseDTO
{
    public function __construct(
        public EventOutcome $outcome,
    ) {}
}
