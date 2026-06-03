<?php

declare(strict_types=1);

namespace App\Modules\Admin\Http\Controllers;

use App\Modules\Admin\Http\Requests\RejectDepositRequest;
use App\Modules\Admin\Http\Resources\AdminDepositResource;
use App\Modules\Admin\Services\AdminDepositServiceInterface;
use App\Modules\Wallet\Enums\DepositStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;

class AdminDepositController extends Controller
{
    public function __construct(
        private readonly AdminDepositServiceInterface $service,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $statusValue = $request->input('status');
        $status      = null;

        if ($statusValue !== null && $statusValue !== '') {
            $status = DepositStatus::from((int) $statusValue)->value;
        }

        $deposits = $this->service->list(
            filters: ['status' => $status],
            perPage: (int) $request->input('per_page', 20),
        );

        return AdminDepositResource::collection($deposits);
    }

    public function show(string $ulid): JsonResponse
    {
        return response()->json(
            new AdminDepositResource($this->service->findByUlid($ulid))
        );
    }

    public function reject(RejectDepositRequest $request, string $ulid): JsonResponse
    {
        $deposit = $this->service->reject($ulid, $request->validated('reason'));

        return response()->json(new AdminDepositResource($deposit));
    }

    public function confirm(string $ulid): JsonResponse
    {
        $deposit = $this->service->confirm($ulid);

        return response()->json(new AdminDepositResource($deposit));
    }
}
