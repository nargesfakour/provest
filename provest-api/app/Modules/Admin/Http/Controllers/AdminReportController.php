<?php

declare(strict_types=1);

namespace App\Modules\Admin\Http\Controllers;

use App\Modules\Admin\Http\Resources\AdminUserResource;
use App\Modules\Admin\Services\AdminReportServiceInterface;
use App\Modules\Trades\Http\Resources\TradeResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;

class AdminReportController extends Controller
{
    public function __construct(
        private readonly AdminReportServiceInterface $service,
    ) {}

    public function fees(Request $request): JsonResponse
    {
        $data = $this->service->fees([
            'from'       => $request->input('from'),
            'to'         => $request->input('to'),
            'event_ulid' => $request->input('event_ulid'),
        ]);

        return response()->json($data);
    }

    public function trades(Request $request): AnonymousResourceCollection
    {
        $trades = $this->service->trades(
            filters: [
                'from'       => $request->input('from'),
                'to'         => $request->input('to'),
                'event_ulid' => $request->input('event_ulid'),
            ],
            perPage: (int) $request->input('per_page', 20),
        );

        return TradeResource::collection($trades);
    }

    public function wallets(Request $request): AnonymousResourceCollection
    {
        $users = $this->service->wallets(
            perPage: (int) $request->input('per_page', 20),
        );

        return AdminUserResource::collection($users);
    }

    public function balances(Request $request): AnonymousResourceCollection
    {
        return $this->wallets($request);
    }

    public function volume(Request $request): JsonResponse
    {
        $rows = $this->service->volume([
            'from' => $request->input('from'),
            'to'   => $request->input('to'),
        ]);

        return response()->json([
            'data' => $rows->map(fn ($row) => [
                'date'        => $row->date,
                'volume_usdt' => number_format((float) $row->volume_usdt, 8, '.', ''),
                'fees_usdt'   => number_format((float) $row->fees_usdt,   8, '.', ''),
                'trade_count' => (int) $row->trade_count,
            ])->values(),
        ]);
    }
}
