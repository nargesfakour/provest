<?php

declare(strict_types=1);

namespace App\Modules\Orders\Http\Controllers;

use App\Modules\Orders\Enums\OrderSide;
use App\Modules\Orders\Http\Requests\PlaceOrderRequest;
use App\Modules\Orders\Http\Resources\OrderResource;
use App\Modules\Orders\Services\OrderServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;

class OrderController extends Controller
{
    public function __construct(
        private readonly OrderServiceInterface $orderService,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $user = $request->user('users');

        $orders = $this->orderService->listForUser(
            userId:  $user->id,
            filters: [
                'status'     => $request->input('status'),
                'event_ulid' => $request->input('event_ulid'),
            ],
            perPage: (int) $request->input('per_page', 20),
        );

        return OrderResource::collection($orders);
    }

    public function store(PlaceOrderRequest $request): JsonResponse
    {
        $user = $request->user('users');

        $side = $request->validated('side') === 'yes'
            ? OrderSide::Yes
            : OrderSide::No;

        $order = $this->orderService->place(
            userId:         $user->id,
            eventUlid:      $request->validated('event_ulid'),
            side:           $side,
            price:          bcmul((string) $request->validated('price'), '1', 4),
            quantity:       bcmul((string) $request->validated('quantity'), '1', 8),
            idempotencyKey: $request->validated('idempotency_key'),
        );

        return response()->json(new OrderResource($order), 201);
    }

    public function destroy(Request $request, string $ulid): JsonResponse
    {
        $user  = $request->user('users');
        $order = $this->orderService->cancel($user->id, $ulid);

        return response()->json(new OrderResource($order));
    }
}
