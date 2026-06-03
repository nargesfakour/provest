<?php

declare(strict_types=1);

namespace App\Modules\Settlement\Repositories;

use App\Modules\Settlement\Models\Settlement;

interface SettlementRepositoryInterface
{
    public function create(array $data): Settlement;

    public function existsForEvent(int $eventId): bool;
}
