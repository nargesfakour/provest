<?php

declare(strict_types=1);

namespace App\Modules\Settlement\Services;

use App\Modules\Markets\Enums\EventOutcome;

interface SettlementServiceInterface
{
    public function settle(int $eventId, EventOutcome $outcome): void;
}
