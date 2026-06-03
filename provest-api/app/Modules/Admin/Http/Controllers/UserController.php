<?php

declare(strict_types=1);

namespace App\Modules\Admin\Http\Controllers;

use App\Modules\Admin\Http\Resources\AdminDepositResource;
use App\Modules\Admin\Http\Resources\AdminUserResource;
use App\Modules\Admin\Http\Resources\WalletTransactionResource;
use App\Modules\Admin\Services\UserManagementServiceInterface;
use App\Modules\Orders\Http\Resources\OrderResource;
use App\Modules\Positions\Http\Resources\PositionResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;

class UserController extends Controller
{
    public function __construct(
        private readonly UserManagementServiceInterface $userService,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $users = $this->userService->list(
            filters: [
                'status' => $request->input('status'),
                'search' => $request->input('search'),
            ],
            perPage: (int) $request->input('per_page', 20),
        );

        return AdminUserResource::collection($users);
    }

    public function show(string $ulid): JsonResponse
    {
        return response()->json(
            new AdminUserResource($this->userService->findByUlid($ulid))
        );
    }

    public function activate(string $ulid): JsonResponse
    {
        return response()->json(
            new AdminUserResource($this->userService->activate($ulid))
        );
    }

    public function deactivate(string $ulid): JsonResponse
    {
        return response()->json(
            new AdminUserResource($this->userService->deactivate($ulid))
        );
    }

    public function wallet(Request $request, string $ulid): JsonResponse
    {
        $user = $this->userService->findByUlid($ulid);

        $transactions = $this->userService->walletTransactions(
            ulid:    $ulid,
            perPage: (int) $request->input('per_page', 20),
        );

        return response()->json([
            'balance'      => $user->balance,
            'transactions' => WalletTransactionResource::collection($transactions)->response()->getData(true),
        ]);
    }

    public function positions(Request $request, string $ulid): AnonymousResourceCollection
    {
        $positions = $this->userService->positions(
            ulid:    $ulid,
            perPage: (int) $request->input('per_page', 20),
        );

        return PositionResource::collection($positions);
    }

    public function orders(Request $request, string $ulid): AnonymousResourceCollection
    {
        $orders = $this->userService->orders(
            ulid:    $ulid,
            perPage: (int) $request->input('per_page', 20),
        );

        return OrderResource::collection($orders);
    }

    public function transactions(Request $request, string $ulid): AnonymousResourceCollection
    {
        $txs = $this->userService->walletTransactions(
            ulid:    $ulid,
            perPage: (int) $request->input('per_page', 20),
        );

        return WalletTransactionResource::collection($txs);
    }

    public function deposits(Request $request, string $ulid): AnonymousResourceCollection
    {
        $deposits = $this->userService->deposits(
            ulid:    $ulid,
            perPage: (int) $request->input('per_page', 20),
        );

        return AdminDepositResource::collection($deposits);
    }
}
