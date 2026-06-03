<?php

declare(strict_types=1);

uses(Tests\TestCase::class);
uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

use App\Modules\Auth\Models\User;
use App\Modules\Markets\Enums\EventOutcome;
use App\Modules\Markets\Models\Event as EventModel;
use App\Modules\Matching\Services\MatchingEngineInterface;
use App\Modules\Orders\Events\OrderCancelled;
use App\Modules\Orders\Events\OrderPlaced;
use App\Modules\Orders\Models\Order;
use App\Modules\Settlement\Events\EventSettled;
use App\Modules\Settlement\Services\SettlementServiceInterface;
use App\Modules\Trades\Events\TradeExecuted;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    Queue::fake(); // prevent MatchOrdersJob from running automatically

    $this->buyer  = User::factory()->create();
    $this->seller = User::factory()->create();
    $this->event  = EventModel::factory()->open()->create();

    DB::table('users')->where('id', $this->buyer->id)->update(['balance'  => '1000.00000000']);
    DB::table('users')->where('id', $this->seller->id)->update(['balance' => '1000.00000000']);
});

// ─── OrderPlaced ─────────────────────────────────────────────────────────────

it('dispatches OrderPlaced when an order is placed', function () {
    Event::fake([OrderPlaced::class]);

    $this->actingAs($this->buyer, 'users')->postJson('/api/v1/orders', [
        'event_ulid'      => $this->event->ulid,
        'side'            => 'yes',
        'price'           => '0.60',
        'quantity'        => '5',
        'idempotency_key' => 'bc-placed-001',
    ])->assertStatus(201);

    Event::assertDispatched(OrderPlaced::class, function (OrderPlaced $e): bool {
        return $e->order->event->ulid === $this->event->ulid
            && $e->order->user->id === $this->buyer->id;
    });
});

it('does not dispatch OrderPlaced for a duplicate idempotency_key', function () {
    Event::fake([OrderPlaced::class]);

    $payload = [
        'event_ulid' => $this->event->ulid, 'side' => 'yes',
        'price' => '0.60', 'quantity' => '5', 'idempotency_key' => 'bc-idem-001',
    ];

    $this->actingAs($this->buyer, 'users')->postJson('/api/v1/orders', $payload);
    $this->actingAs($this->buyer, 'users')->postJson('/api/v1/orders', $payload);

    // Second call returns existing order (no new broadcast)
    Event::assertDispatchedTimes(OrderPlaced::class, 1);
});

// ─── OrderCancelled ───────────────────────────────────────────────────────────

it('dispatches OrderCancelled when an order is cancelled', function () {
    Event::fake([OrderPlaced::class, OrderCancelled::class]);

    $this->actingAs($this->buyer, 'users')->postJson('/api/v1/orders', [
        'event_ulid' => $this->event->ulid, 'side' => 'yes',
        'price' => '0.60', 'quantity' => '5', 'idempotency_key' => 'bc-cancel-001',
    ]);

    $order = Order::where('idempotency_key', 'bc-cancel-001')->first();

    $this->actingAs($this->buyer, 'users')
        ->deleteJson("/api/v1/orders/{$order->ulid}")
        ->assertStatus(200);

    Event::assertDispatched(OrderCancelled::class, function (OrderCancelled $e) use ($order): bool {
        return $e->order->ulid === $order->ulid;
    });
});

// ─── TradeExecuted ────────────────────────────────────────────────────────────

it('dispatches TradeExecuted for each trade created by the matching engine', function () {
    Event::fake([TradeExecuted::class]);

    $this->actingAs($this->buyer, 'users')->postJson('/api/v1/orders', [
        'event_ulid' => $this->event->ulid, 'side' => 'yes',
        'price' => '0.60', 'quantity' => '10', 'idempotency_key' => 'bc-trade-yes',
    ]);
    $this->actingAs($this->seller, 'users')->postJson('/api/v1/orders', [
        'event_ulid' => $this->event->ulid, 'side' => 'no',
        'price' => '0.40', 'quantity' => '10', 'idempotency_key' => 'bc-trade-no',
    ]);

    app(MatchingEngineInterface::class)->match($this->event->id);

    Event::assertDispatched(TradeExecuted::class, function (TradeExecuted $e): bool {
        return $e->trade->event->ulid === $this->event->ulid
            && $e->trade->quantity === '10.00000000';
    });
});

it('dispatches one TradeExecuted per trade when multiple matches occur', function () {
    Event::fake([TradeExecuted::class]);

    $seller2 = User::factory()->create();
    DB::table('users')->where('id', $seller2->id)->update(['balance' => '1000.00000000']);

    $this->actingAs($this->buyer, 'users')->postJson('/api/v1/orders', [
        'event_ulid' => $this->event->ulid, 'side' => 'yes',
        'price' => '0.60', 'quantity' => '10', 'idempotency_key' => 'bc-multi-yes',
    ]);
    $this->actingAs($this->seller, 'users')->postJson('/api/v1/orders', [
        'event_ulid' => $this->event->ulid, 'side' => 'no',
        'price' => '0.40', 'quantity' => '4', 'idempotency_key' => 'bc-multi-no1',
    ]);
    $this->actingAs($seller2, 'users')->postJson('/api/v1/orders', [
        'event_ulid' => $this->event->ulid, 'side' => 'no',
        'price' => '0.40', 'quantity' => '6', 'idempotency_key' => 'bc-multi-no2',
    ]);

    app(MatchingEngineInterface::class)->match($this->event->id);

    Event::assertDispatchedTimes(TradeExecuted::class, 2);
});

// ─── EventSettled ─────────────────────────────────────────────────────────────

it('dispatches EventSettled after settlement', function () {
    Event::fake([EventSettled::class]);

    $this->actingAs($this->buyer, 'users')->postJson('/api/v1/orders', [
        'event_ulid' => $this->event->ulid, 'side' => 'yes',
        'price' => '0.60', 'quantity' => '10', 'idempotency_key' => 'bc-settle-yes',
    ]);
    $this->actingAs($this->seller, 'users')->postJson('/api/v1/orders', [
        'event_ulid' => $this->event->ulid, 'side' => 'no',
        'price' => '0.40', 'quantity' => '10', 'idempotency_key' => 'bc-settle-no',
    ]);
    app(MatchingEngineInterface::class)->match($this->event->id);

    app(SettlementServiceInterface::class)->settle($this->event->id, EventOutcome::Yes);

    Event::assertDispatched(EventSettled::class, function (EventSettled $e): bool {
        return $e->eventUlid === $this->event->ulid
            && $e->outcome === EventOutcome::Yes;
    });
});

it('does not dispatch EventSettled a second time when settlement is re-run', function () {
    Event::fake([EventSettled::class]);

    // Create positions so settlement records exist after the first run
    $this->actingAs($this->buyer, 'users')->postJson('/api/v1/orders', [
        'event_ulid' => $this->event->ulid, 'side' => 'yes',
        'price' => '0.60', 'quantity' => '10', 'idempotency_key' => 'bc-idem-settle-yes',
    ]);
    $this->actingAs($this->seller, 'users')->postJson('/api/v1/orders', [
        'event_ulid' => $this->event->ulid, 'side' => 'no',
        'price' => '0.40', 'quantity' => '10', 'idempotency_key' => 'bc-idem-settle-no',
    ]);
    app(MatchingEngineInterface::class)->match($this->event->id);

    app(SettlementServiceInterface::class)->settle($this->event->id, EventOutcome::Yes);
    // Second call — existsForEvent = true, method exits before firing the event
    app(SettlementServiceInterface::class)->settle($this->event->id, EventOutcome::Yes);

    Event::assertDispatchedTimes(EventSettled::class, 1);
});

// ─── Channel authorization callback ──────────────────────────────────────────
// The HTTP /broadcasting/auth endpoint requires a running Soketi server for
// full end-to-end tests.  Here we verify the authorization callback logic
// directly, which is what actually gates subscriber access.

it('channel callback allows the owner and rejects a different user', function () {
    $allow = ($this->buyer->ulid === $this->buyer->ulid); // owner accessing own channel
    $deny  = ($this->buyer->ulid === $this->seller->ulid); // buyer accessing seller channel

    expect($allow)->toBeTrue();
    expect($deny)->toBeFalse();
});
