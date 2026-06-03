<?php

declare(strict_types=1);

uses(Tests\TestCase::class);
uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

use App\Modules\Auth\Models\User;
use App\Modules\Markets\Models\Event;
use App\Modules\Orders\Enums\OrderSide;
use App\Modules\Orders\Enums\OrderStatus;
use App\Modules\Orders\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    Queue::fake(); // MatchOrdersJob must be queued, not run synchronously in tests

    $this->user  = User::factory()->create();
    $this->event = Event::factory()->open()->create();

    DB::table('users')->where('id', $this->user->id)->update(['balance' => '1000.00000000']);
});

// ─── POST /api/v1/orders ──────────────────────────────────────────────────────

it('places a YES order, locks funds, and dispatches MatchOrdersJob', function () {
    $response = $this->actingAs($this->user, 'users')
        ->postJson('/api/v1/orders', [
            'event_ulid'      => $this->event->ulid,
            'side'            => 'yes',
            'price'           => '0.60',
            'quantity'        => '10',
            'idempotency_key' => 'order-test-001',
        ]);

    $response->assertStatus(201)
        ->assertJsonPath('side', 'Yes')
        ->assertJsonPath('price', '0.6000')
        ->assertJsonPath('quantity', '10.00000000')
        ->assertJsonPath('status', 'Open');

    // lock = 0.60 * 10 = 6.00
    $this->assertDatabaseHas('users', [
        'id'      => $this->user->id,
        'balance' => '994.00000000',
    ]);

    $this->assertDatabaseHas('orders', [
        'user_id'         => $this->user->id,
        'side'            => OrderSide::Yes->value,
        'status'          => OrderStatus::Open->value,
        'idempotency_key' => 'order-test-001',
    ]);

    $this->assertDatabaseHas('wallet_transactions', [
        'user_id' => $this->user->id,
        'amount'  => '-6.00000000',
    ]);

    Queue::assertPushed(\App\Modules\Matching\Jobs\MatchOrdersJob::class);
});

it('places a NO order and locks funds at the correct price', function () {
    $response = $this->actingAs($this->user, 'users')
        ->postJson('/api/v1/orders', [
            'event_ulid'      => $this->event->ulid,
            'side'            => 'no',
            'price'           => '0.40',
            'quantity'        => '5',
            'idempotency_key' => 'order-test-002',
        ]);

    $response->assertStatus(201)
        ->assertJsonPath('side', 'No');

    // lock = 0.40 * 5 = 2.00
    $this->assertDatabaseHas('users', [
        'id'      => $this->user->id,
        'balance' => '998.00000000',
    ]);
});

it('is idempotent — duplicate idempotency_key returns existing order without double-locking', function () {
    $payload = [
        'event_ulid'      => $this->event->ulid,
        'side'            => 'yes',
        'price'           => '0.50',
        'quantity'        => '4',
        'idempotency_key' => 'idem-order-001',
    ];

    $r1 = $this->actingAs($this->user, 'users')->postJson('/api/v1/orders', $payload);
    $r2 = $this->actingAs($this->user, 'users')->postJson('/api/v1/orders', $payload);

    expect($r1->json('ulid'))->toBe($r2->json('ulid'));

    // 0.50 * 4 = 2.00 locked only once
    $this->assertDatabaseHas('users', ['id' => $this->user->id, 'balance' => '998.00000000']);
    $this->assertDatabaseCount('orders', 1);
    $this->assertDatabaseCount('wallet_transactions', 1);
});

it('returns 422 when balance is insufficient', function () {
    DB::table('users')->where('id', $this->user->id)->update(['balance' => '1.00000000']);

    $this->actingAs($this->user, 'users')
        ->postJson('/api/v1/orders', [
            'event_ulid'      => $this->event->ulid,
            'side'            => 'yes',
            'price'           => '0.90',
            'quantity'        => '100',
            'idempotency_key' => 'insufficient-001',
        ])
        ->assertStatus(422);

    $this->assertDatabaseCount('orders', 0);
    $this->assertDatabaseCount('wallet_transactions', 0);
});

it('returns 422 when event is not Open', function () {
    $pendingEvent = Event::factory()->create(); // default status = Pending

    $this->actingAs($this->user, 'users')
        ->postJson('/api/v1/orders', [
            'event_ulid'      => $pendingEvent->ulid,
            'side'            => 'yes',
            'price'           => '0.50',
            'quantity'        => '1',
            'idempotency_key' => 'pending-event-001',
        ])
        ->assertStatus(422)
        ->assertJsonFragment(['message' => 'Orders can only be placed on Open events']);
});

it('returns 404 when event does not exist', function () {
    $this->actingAs($this->user, 'users')
        ->postJson('/api/v1/orders', [
            'event_ulid'      => '01NONEXISTENT0000000000000',
            'side'            => 'yes',
            'price'           => '0.50',
            'quantity'        => '1',
            'idempotency_key' => 'no-event-001',
        ])
        ->assertStatus(404);
});

it('returns 422 when price is out of range', function () {
    $this->actingAs($this->user, 'users')
        ->postJson('/api/v1/orders', [
            'event_ulid'      => $this->event->ulid,
            'side'            => 'yes',
            'price'           => '1.00',
            'quantity'        => '1',
            'idempotency_key' => 'bad-price-001',
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['price']);
});

it('returns 422 when required fields are missing', function () {
    $this->actingAs($this->user, 'users')
        ->postJson('/api/v1/orders', [])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['event_ulid', 'side', 'price', 'quantity', 'idempotency_key']);
});

it('returns 401 when unauthenticated', function () {
    $this->postJson('/api/v1/orders', [])->assertStatus(401);
});

// ─── DELETE /api/v1/orders/{ulid} ─────────────────────────────────────────────

it('cancels an open order and unlocks remaining funds', function () {
    // Place order first
    $this->actingAs($this->user, 'users')->postJson('/api/v1/orders', [
        'event_ulid'      => $this->event->ulid,
        'side'            => 'yes',
        'price'           => '0.50',
        'quantity'        => '10',
        'idempotency_key' => 'cancel-test-001',
    ])->assertStatus(201);

    $order = Order::where('idempotency_key', 'cancel-test-001')->first();

    // Balance should be 1000 - (0.50*10=5) = 995
    $this->assertDatabaseHas('users', ['id' => $this->user->id, 'balance' => '995.00000000']);

    $this->actingAs($this->user, 'users')
        ->deleteJson("/api/v1/orders/{$order->ulid}")
        ->assertStatus(200)
        ->assertJsonPath('status', 'Cancelled');

    // Funds restored: 995 + 5 = 1000
    $this->assertDatabaseHas('users', ['id' => $this->user->id, 'balance' => '1000.00000000']);
    $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => OrderStatus::Cancelled->value]);
});

it('returns 403 when cancelling another user order', function () {
    $other = User::factory()->create();
    DB::table('users')->where('id', $other->id)->update(['balance' => '500.00000000']);

    $this->actingAs($other, 'users')->postJson('/api/v1/orders', [
        'event_ulid'      => $this->event->ulid,
        'side'            => 'yes',
        'price'           => '0.50',
        'quantity'        => '2',
        'idempotency_key' => 'other-order-001',
    ]);

    $order = Order::where('idempotency_key', 'other-order-001')->first();

    $this->actingAs($this->user, 'users')
        ->deleteJson("/api/v1/orders/{$order->ulid}")
        ->assertStatus(403);
});

it('returns 422 when cancelling an already-cancelled order', function () {
    $this->actingAs($this->user, 'users')->postJson('/api/v1/orders', [
        'event_ulid'      => $this->event->ulid,
        'side'            => 'yes',
        'price'           => '0.50',
        'quantity'        => '2',
        'idempotency_key' => 'cancel-twice-001',
    ]);

    $order = Order::where('idempotency_key', 'cancel-twice-001')->first();

    $this->actingAs($this->user, 'users')->deleteJson("/api/v1/orders/{$order->ulid}");

    $this->actingAs($this->user, 'users')
        ->deleteJson("/api/v1/orders/{$order->ulid}")
        ->assertStatus(422);
});

// ─── GET /api/v1/orders ───────────────────────────────────────────────────────

it('lists authenticated user orders', function () {
    $this->actingAs($this->user, 'users')->postJson('/api/v1/orders', [
        'event_ulid'      => $this->event->ulid,
        'side'            => 'yes',
        'price'           => '0.50',
        'quantity'        => '1',
        'idempotency_key' => 'list-test-001',
    ]);

    $this->actingAs($this->user, 'users')
        ->getJson('/api/v1/orders')
        ->assertStatus(200)
        ->assertJsonCount(1, 'data');
});

it('does not expose another user orders in the list', function () {
    $other = User::factory()->create();
    DB::table('users')->where('id', $other->id)->update(['balance' => '500.00000000']);

    $this->actingAs($other, 'users')->postJson('/api/v1/orders', [
        'event_ulid'      => $this->event->ulid,
        'side'            => 'no',
        'price'           => '0.50',
        'quantity'        => '1',
        'idempotency_key' => 'other-list-001',
    ]);

    $this->actingAs($this->user, 'users')
        ->getJson('/api/v1/orders')
        ->assertStatus(200)
        ->assertJsonCount(0, 'data');
});
