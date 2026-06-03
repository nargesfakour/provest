<?php

declare(strict_types=1);

namespace App\Modules\Admin\Http\Controllers;

use App\Modules\Admin\Http\Resources\AdminWithdrawalResource;
use App\Modules\Admin\Services\AdminWithdrawalServiceInterface;
use App\Modules\Wallet\Enums\WithdrawalStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;

class AdminWithdrawalController extends Controller
{
    public function __construct(
        private readonly AdminWithdrawalServiceInterface $service,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $statusValue = $request->input('status');
        $status      = null;

        if ($statusValue !== null && $statusValue !== '') {
            $status = WithdrawalStatus::from((int) $statusValue)->value;
        }

        $withdrawals = $this->service->list(
            filters: ['status' => $status],
            perPage: (int) $request->input('per_page', 20),
        );

        return AdminWithdrawalResource::collection($withdrawals);
    }

    public function show(string $ulid): JsonResponse
    {
        return response()->json(
            new AdminWithdrawalResource($this->service->findByUlid($ulid))
        );
    }

    public function approve(string $ulid): JsonResponse
    {
        $withdrawal = $this->service->approve($ulid);

        return response()->json(new AdminWithdrawalResource($withdrawal));
    }

    public function reject(string $ulid): JsonResponse
    {
        $withdrawal = $this->service->reject($ulid);

        return response()->json(new AdminWithdrawalResource($withdrawal));
    }
}
