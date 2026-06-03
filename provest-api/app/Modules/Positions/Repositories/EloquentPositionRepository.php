<?php

declare(strict_types=1);

namespace App\Modules\Positions\Repositories;

use App\Modules\Positions\Models\Position;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class EloquentPositionRepository implements PositionRepositoryInterface
{
    public function upsert(int $userId, int $eventId, int $side, string $addQty, string $tradePrice): Position
    {
        $position = Position::where('user_id', $userId)
            ->where('event_id', $eventId)
            ->where('side', $side)
            ->lockForUpdate()
            ->first();

        if ($position === null) {
            /** @var Position $position */
            $position = Position::create([
                'user_id'   => $userId,
                'event_id'  => $eventId,
                'side'      => $side,
                'quantity'  => $addQty,
                'avg_price' => $tradePrice,
            ]);

            return $position;
        }

        $newQty = bcadd($position->quantity, $addQty, 8);

        $totalCost = bcadd(
            bcmul($position->quantity, $position->avg_price, 8),
            bcmul($addQty, $tradePrice, 8),
            8
        );

        $newAvgPrice = bcdiv($totalCost, $newQty, 8);

        Position::where('id', $position->id)->update([
            'quantity'  => $newQty,
            'avg_price' => $newAvgPrice,
        ]);

        $position->quantity  = $newQty;
        $position->avg_price = $newAvgPrice;

        return $position;
    }

    public function paginateForUser(int $userId, int $perPage): LengthAwarePaginator
    {
        return Position::with('event')
            ->where('user_id', $userId)
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    public function getForEvent(int $eventId): Collection
    {
        return Position::where('event_id', $eventId)
            ->where('quantity', '>', 0)
            ->get();
    }

    public function lockForSettlement(int $userId, int $eventId, int $side): ?Position
    {
        /** @var Position|null $position */
        $position = Position::where('user_id', $userId)
            ->where('event_id', $eventId)
            ->where('side', $side)
            ->lockForUpdate()
            ->first();

        return $position;
    }
}
