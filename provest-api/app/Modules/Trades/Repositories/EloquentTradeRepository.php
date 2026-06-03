<?php

declare(strict_types=1);

namespace App\Modules\Trades\Repositories;

use App\Modules\Trades\Models\Trade;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class EloquentTradeRepository implements TradeRepositoryInterface
{
    public function create(array $data): Trade
    {
        /** @var Trade $trade */
        $trade = Trade::create($data);

        return $trade;
    }

    public function paginateForEvent(int $eventId, int $perPage): LengthAwarePaginator
    {
        return Trade::where('event_id', $eventId)
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    public function paginateForUser(int $userId, int $perPage): LengthAwarePaginator
    {
        return Trade::where('yes_user_id', $userId)
            ->orWhere('no_user_id', $userId)
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    public function sumFeesForEvent(int $eventId): array
    {
        $result = DB::table('trades')
            ->where('event_id', $eventId)
            ->selectRaw(
                'COALESCE(SUM(yes_fee::numeric), 0) AS total_yes_fee,' .
                ' COALESCE(SUM(no_fee::numeric), 0)  AS total_no_fee'
            )
            ->first();

        return [
            'yes_fee' => (string) ($result->total_yes_fee ?? '0'),
            'no_fee'  => (string) ($result->total_no_fee  ?? '0'),
        ];
    }

    public function sumFeesWithFilters(array $filters): array
    {
        $query = DB::table('trades');

        if (!empty($filters['event_id'])) {
            $query->where('event_id', $filters['event_id']);
        }

        if (!empty($filters['from'])) {
            $query->whereDate('created_at', '>=', $filters['from']);
        }

        if (!empty($filters['to'])) {
            $query->whereDate('created_at', '<=', $filters['to']);
        }

        $result = $query->selectRaw(
            'COALESCE(SUM(yes_fee::numeric), 0) AS total_yes_fee,' .
            ' COALESCE(SUM(no_fee::numeric), 0)  AS total_no_fee'
        )->first();

        $yes = (string) ($result->total_yes_fee ?? '0');
        $no  = (string) ($result->total_no_fee  ?? '0');

        return [
            'yes_fee'   => $yes,
            'no_fee'    => $no,
            'total_fee' => bcadd($yes, $no, 8),
        ];
    }

    public function paginateWithFilters(array $filters, int $perPage): LengthAwarePaginator
    {
        $query = Trade::query()->orderByDesc('id');

        if (!empty($filters['event_id'])) {
            $query->where('event_id', $filters['event_id']);
        }

        if (!empty($filters['from'])) {
            $query->whereDate('created_at', '>=', $filters['from']);
        }

        if (!empty($filters['to'])) {
            $query->whereDate('created_at', '<=', $filters['to']);
        }

        return $query->paginate($perPage);
    }

    public function dailyVolume(array $filters): Collection
    {
        $query = DB::table('trades');

        if (!empty($filters['from'])) {
            $query->whereDate('created_at', '>=', $filters['from']);
        }

        if (!empty($filters['to'])) {
            $query->whereDate('created_at', '<=', $filters['to']);
        }

        return $query
            ->selectRaw(
                "DATE(created_at) AS date," .
                " COALESCE(SUM(quantity::numeric), 0) AS volume_usdt," .
                " COALESCE(SUM((yes_fee + no_fee)::numeric), 0) AS fees_usdt," .
                " COUNT(*) AS trade_count"
            )
            ->groupByRaw('DATE(created_at)')
            ->orderByRaw('DATE(created_at)')
            ->get();
    }
}
