<?php

declare(strict_types=1);

namespace App\Modules\Admin\Services;

use App\Modules\Markets\Enums\EventStatus;
use Illuminate\Support\Facades\DB;

class AdminDashboardService implements AdminDashboardServiceInterface
{
    public function stats(): array
    {
        $totalUsers = DB::table('users')->count();

        $activeEvents = DB::table('events')
            ->where('status', EventStatus::Open->value)
            ->count();

        $totalVolume = DB::table('trades')
            ->selectRaw('COALESCE(SUM(quantity::numeric), 0) AS v')
            ->value('v');

        $todayFees = DB::table('trades')
            ->whereDate('created_at', now()->toDateString())
            ->selectRaw('COALESCE(SUM((yes_fee + no_fee)::numeric), 0) AS f')
            ->value('f');

        return [
            'total_users'       => $totalUsers,
            'active_events'     => $activeEvents,
            'total_volume_usdt' => number_format((float) $totalVolume, 8, '.', ''),
            'today_fees_usdt'   => number_format((float) $todayFees,   8, '.', ''),
        ];
    }
}
