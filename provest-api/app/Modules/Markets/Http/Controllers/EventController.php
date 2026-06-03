<?php

declare(strict_types=1);

namespace App\Modules\Markets\Http\Controllers;

use App\Modules\Markets\Enums\EventStatus;
use App\Modules\Markets\Http\Requests\SettleEventRequest;
use App\Modules\Markets\Http\Requests\StoreEventRequest;
use App\Modules\Markets\Http\Requests\UpdateEventRequest;
use App\Modules\Markets\Http\Resources\EventResource;
use App\Modules\Markets\Services\EventServiceInterface;
use App\Modules\Orders\Http\Resources\OrderResource;
use App\Modules\Positions\Http\Resources\PositionResource;
use App\Modules\Trades\Http\Resources\TradeResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;

class EventController extends Controller
{
    public function __construct(
        private readonly EventServiceInterface $eventService,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $events = $this->eventService->list(
            filters: [
                'status'       => $request->input('status'),
                'category'     => $request->input('category'),
                'sub_category' => $request->input('sub_category'),
            ],
            perPage: (int) $request->input('per_page', 20),
        );

        return EventResource::collection($events);
    }

    public function store(StoreEventRequest $request): JsonResponse
    {
        $event = $this->eventService->create(
            $request->toDTO($request->user()->id)
        );

        return response()->json(new EventResource($event), 201);
    }

    public function show(string $ulid): JsonResponse
    {
        return response()->json(new EventResource($this->eventService->findByUlid($ulid)));
    }

    public function update(string $ulid, UpdateEventRequest $request): JsonResponse
    {
        $event = $this->eventService->update($ulid, $request->toDTO());

        return response()->json(new EventResource($event));
    }

    public function destroy(string $ulid): JsonResponse
    {
        $this->eventService->delete($ulid);

        return response()->json(null, 204);
    }

    public function open(string $ulid): JsonResponse
    {
        return response()->json(new EventResource($this->eventService->open($ulid)));
    }

    public function close(string $ulid): JsonResponse
    {
        return response()->json(new EventResource($this->eventService->close($ulid)));
    }

    public function settle(string $ulid, SettleEventRequest $request): JsonResponse
    {
        $event = $this->eventService->settle($ulid, $request->toDTO());

        return response()->json(new EventResource($event));
    }

    public function orderbook(string $ulid): JsonResponse
    {
        return response()->json($this->eventService->getOrderbook($ulid));
    }

    public function orders(Request $request, string $ulid): AnonymousResourceCollection
    {
        $orders = $this->eventService->paginateOrders($ulid, (int) $request->input('per_page', 20));

        return OrderResource::collection($orders);
    }

    public function trades(Request $request, string $ulid): AnonymousResourceCollection
    {
        $trades = $this->eventService->paginateTrades($ulid, (int) $request->input('per_page', 20));

        return TradeResource::collection($trades);
    }

    public function positions(Request $request, string $ulid): AnonymousResourceCollection
    {
        $positions = $this->eventService->paginatePositions($ulid, (int) $request->input('per_page', 20));

        return PositionResource::collection($positions);
    }
}
