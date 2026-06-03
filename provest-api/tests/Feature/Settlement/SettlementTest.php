<?php

declare(strict_types=1);

uses(Tests\TestCase::class);
uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

use App\Modules\Auth\Models\User;
use App\Modules\Markets\Enums\EventOutcome;
use App\Modules\Markets\Models\Event;
use App\Modules\Matching\Services\MatchingEngineInterface;
use App\Modules\Orders\Enums\OrderStatus;
use App\Modules\Orders\Models\Order;
use App\Modules\Settlement\Models\Settlement;
use App\Modules\Settlement\Services\SettlementServiceInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    Queue::fake();

    $this->buyer  = User::factory()->create();
    $this->seller = User::factory()->create();
    // fee_rate = 0.0100 (1%) set by EventFactory
    $this->event  = Event::factory()->open()->create();

    DB::table('users')->where('id', $this->buyer->id)->update(['balance'  => '1000.00000000']);
    DB::table('users')->where('id', $this->seller->id)->update(['balance' => '1000.00000000']);
});

function matchOrders(mixed $ctx): void
{
    app(MatchingEngineInterface::class)->match($ctx->event->id);
}

function settle(mixed $ctx, EventOutcome $outcome): void
{
    app(SettlementServiceInterface::class)->settle($ctx->event->id, $outcome);
}

// ─── YES wins ────────────────────────────────────────────────────────────────

it('pays YES holders and ignores NO holders when outcome is YES', function () {
    // buyer places YES 0.60 × 10  → locks 6.00
    $this->actingAs($this->buyer, 'users')->postJson('/api/v1/orders', [
        'event_ulid' => $this->event->ulid, 'side' => 'yes',
        'price' => '0.60', 'quantity' => '10', 'idempotency_key' => 'yes-win-yes',
    ]);
    // seller places NO 0.40 × 10 → locks 4.00
    $this->actingAs($this->seller, 'users')->postJson('/api/v1/orders', [
        'event_ulid' => $this->event->ulid, 'side' => 'no',
        'price' => '0.40', 'quantity' => '10', 'idempotency_key' => 'yes-win-no',
    ]);

    matchOrders($this);
    settle($this, EventOutcome::Yes);

    // YES user: 1000 - 6.00 (lock) + 9.90 (net win = 10×1.00 - 10×0.01) = 1003.90
    $this->assertDatabaseHas('users', ['id' => $this->buyer->id,  'balance' => '1003.90000000']);
    // NO user: 1000 - 4.00 (lock, consumed by losing trade) = 996.00
    $this->assertDatabaseHas('users', ['id' => $this->seller->id, 'balance' => '996.00000000']);

    // One settlement record for the YES winner
    $this->assertDatabaseCount('settlements', 1);
    $this->assertDatabaseHas('settlements', [
        'user_id'  => $this->buyer->id,
        'quantity' => '10.00000000',
        'payout'   => '9.90000000',
    ]);
});

// ─── NO wins ─────────────────────────────────────────────────────────────────

it('pays NO holders and ignores YES holders when outcome is NO', function () {
    $this->actingAs($this->buyer, 'users')->postJson('/api/v1/orders', [
        'event_ulid' => $this->event->ulid, 'side' => 'yes',
        'price' => '0.60', 'quantity' => '10', 'idempotency_key' => 'no-win-yes',
    ]);
    $this->actingAs($this->seller, 'users')->postJson('/api/v1/orders', [
        'event_ulid' => $this->event->ulid, 'side' => 'no',
        'price' => '0.40', 'quantity' => '10', 'idempotency_key' => 'no-win-no',
    ]);

    matchOrders($this);
    settle($this, EventOutcome::No);

    // YES user: 1000 - 6.00 = 994.00
    $this->assertDatabaseHas('users', ['id' => $this->buyer->id,  'balance' => '994.00000000']);
    // NO user: 1000 - 4.00 + 9.90 = 1005.90
    $this->assertDatabaseHas('users', ['id' => $this->seller->id, 'balance' => '1005.90000000']);

    $this->assertDatabaseCount('settlements', 1);
    $this->assertDatabaseHas('settlements', [
        'user_id' => $this->seller->id,
        'payout'  => '9.90000000',
    ]);
});

// ─── Unmatched orders refunded ────────────────────────────────────────────────

it('cancels unmatched orders and returns locked funds at settlement', function () {
    // 0.60 + 0.30 = 0.90 < 1.00 → no match
    $this->actingAs($this->buyer, 'users')->postJson('/api/v1/orders', [
        'event_ulid' => $this->event->ulid, 'side' => 'yes',
        'price' => '0.60', 'quantity' => '10', 'idempotency_key' => 'refund-yes',
    ]);
    $this->actingAs($this->seller, 'users')->postJson('/api/v1/orders', [
        'event_ulid' => $this->event->ulid, 'side' => 'no',
        'price' => '0.30', 'quantity' => '10', 'idempotency_key' => 'refund-no',
    ]);

    matchOrders($this);             // nothing matches
    settle($this, EventOutcome::Yes);

    // All locked funds returned — both balances back to 1000
    $this->assertDatabaseHas('users', ['id' => $this->buyer->id,  'balance' => '1000.00000000']);
    $this->assertDatabaseHas('users', ['id' => $this->seller->id, 'balance' => '1000.00000000']);

    // Orders cancelled
    $this->assertDatabaseHas('orders', ['idempotency_key' => 'refund-yes', 'status' => OrderStatus::Cancelled->value]);
    $this->assertDatabaseHas('orders', ['idempotency_key' => 'refund-no',  'status' => OrderStatus::Cancelled->value]);

    // No payouts (no positions)
    $this->assertDatabaseCount('settlements', 0);
});

// ─── Partial fill → remaining funds refunded ─────────────────────────────────

it('unlocks the unfilled portion of a partially-filled order at settlement', function () {
    // YES 0.60 × 10 → locks 6.00
    $this->actingAs($this->buyer, 'users')->postJson('/api/v1/orders', [
        'event_ulid' => $this->event->ulid, 'side' => 'yes',
        'price' => '0.60', 'quantity' => '10', 'idempotency_key' => 'partial-yes',
    ]);
    // NO 0.40 × 6 → locks 2.40; matches 6 of 10 YES shares
    $this->actingAs($this->seller, 'users')->postJson('/api/v1/orders', [
        'event_ulid' => $this->event->ulid, 'side' => 'no',
        'price' => '0.40', 'quantity' => '6', 'idempotency_key' => 'partial-no',
    ]);

    matchOrders($this);

    $yesOrder = Order::where('idempotency_key', 'partial-yes')->first();
    expect($yesOrder->status)->toBe(OrderStatus::Partial);

    settle($this, EventOutcome::Yes);

    // YES user: 1000 - 6.00 (lock) + 2.40 (unlock 4 unmatched × 0.60) + 5.94 (6 shares × 1.00 - 6 × 0.01) = 1002.34
    $this->assertDatabaseHas('users', ['id' => $this->buyer->id, 'balance' => '1002.34000000']);

    $this->assertDatabaseHas('settlements', ['quantity' => '6.00000000', 'payout' => '5.94000000']);
});

// ─── Idempotency ─────────────────────────────────────────────────────────────

it('is idempotent — running settlement twice does not double-pay', function () {
    $this->actingAs($this->buyer, 'users')->postJson('/api/v1/orders', [
        'event_ulid' => $this->event->ulid, 'side' => 'yes',
        'price' => '0.60', 'quantity' => '10', 'idempotency_key' => 'idem-yes',
    ]);
    $this->actingAs($this->seller, 'users')->postJson('/api/v1/orders', [
        'event_ulid' => $this->event->ulid, 'side' => 'no',
        'price' => '0.40', 'quantity' => '10', 'idempotency_key' => 'idem-no',
    ]);

    matchOrders($this);
    settle($this, EventOutcome::Yes);
    settle($this, EventOutcome::Yes); // second run — should be a no-op

    $this->assertDatabaseCount('settlements', 1);
    $this->assertDatabaseHas('users', ['id' => $this->buyer->id, 'balance' => '1003.90000000']);
});
