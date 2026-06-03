<?php

declare(strict_types=1);

namespace App\Modules\Settlement\Repositories;

use App\Modules\Settlement\Models\Settlement;

class EloquentSettlementRepository implements SettlementRepositoryInterface
{
    public function create(array $data): Settlement
    {
        /** @var Settlement $settlement */
        $settlement = Settlement::create($data);

        return $settlement;
    }

    public function existsForEvent(int $eventId): bool
    {
        return Settlement::where('event_id', $eventId)->exists();
    }
}
