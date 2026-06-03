<?php

declare(strict_types=1);

uses(Tests\TestCase::class);
uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

use App\Modules\Admin\Models\Admin;
use App\Modules\Auth\Models\User;
use App\Modules\Markets\Models\Event;
use App\Modules\Matching\Services\MatchingEngineInterface;
use App\Modules\Settlement\Enums\SettleStatus;
use App\Modules\Settlement\Services\SettlementServiceInterface;
use App\Modules\Markets\Enums\EventOutcome;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    Queue::fake();

    $this->admin  = Admin::factory()->create();
    $this->buyer  = User::factory()->create();
    $this->seller = User::factory()->create();
    $this->event  = Event::factory()->open()->create();

    DB::table('users')->where('id', $this->buyer->id)->update(['balance'  => '1000.00000000']);
    DB::table('users')->where('id', $this->seller->id)->update(['balance' => '1000.00000000']);
});

function createTrade(mixed $ctx): void
{
    $ctx->actingAs($ctx->buyer, 'users')->postJson('/api/v1/orders', [
        'event_ulid' => $ctx->event->ulid, 'side' => 'yes',
        'price' => '0.60', 'quantity' => '10', 'idempotency_key' => 'rep-yes-' . uniqid(),
    ]);
    $ctx->actingAs($ctx->seller, 'users')->postJson('/api/v1/orders', [
        'event_ulid' => $ctx->event->ulid, 'side' => 'no',
        'price' => '0.40', 'quantity' => '10', 'idempotency_key' => 'rep-no-' . uniqid(),
    ]);
    app(MatchingEngineInterface::class)->match($ctx->event->id);
}

// ─── GET /api/v1/admin/reports/fees ──────────────────────────────────────────

it('returns zero fees when no trades exist', function () {
    $this->actingAs($this->admin, 'admins')
        ->getJson('/api/v1/admin/reports/fees')
        ->assertStatus(200)
        ->assertJson([
            'yes_fee'   => '0',
            'no_fee'    => '0',
            'total_fee' => '0.00000000',
        ]);
});

it('returns summed fees across all trades', function () {
    createTrade($this); // yes_fee = no_fee = 10 × 0.01 = 0.10

    $response = $this->actingAs($this->admin, 'admins')
        ->getJson('/api/v1/admin/reports/fees')
        ->assertStatus(200);

    expect($response->json('yes_fee'))->toBe('0.10000000');
    expect($response->json('no_fee'))->toBe('0.10000000');
    expect($response->json('total_fee'))->toBe('0.20000000');
});

it('filters fees by event_ulid', function () {
    createTrade($this);

    $otherEvent = Event::factory()->open()->create();
    // no trades for otherEvent

    $response = $this->actingAs($this->admin, 'admins')
        ->getJson("/api/v1/admin/reports/fees?event_ulid={$this->event->ulid}")
        ->assertStatus(200);

    expect($response->json('yes_fee'))->toBe('0.10000000');

    $response2 = $this->actingAs($this->admin, 'admins')
        ->getJson("/api/v1/admin/reports/fees?event_ulid={$otherEvent->ulid}")
        ->assertStatus(200);

    expect($response2->json('total_fee'))->toBe('0.00000000');
});

it('includes filter metadata in the fees response', function () {
    $response = $this->actingAs($this->admin, 'admins')
        ->getJson('/api/v1/admin/reports/fees?from=2025-01-01&to=2025-12-31')
        ->assertStatus(200);

    expect($response->json('filters.from'))->toBe('2025-01-01');
    expect($response->json('filters.to'))->toBe('2025-12-31');
    expect($response->json('filters.event_ulid'))->toBeNull();
});

// ─── GET /api/v1/admin/reports/trades ────────────────────────────────────────

it('returns a paginated list of all trades', function () {
    createTrade($this);

    $this->actingAs($this->admin, 'admins')
        ->getJson('/api/v1/admin/reports/trades')
        ->assertStatus(200)
        ->assertJsonCount(1, 'data')
        ->assertJsonStructure(['data' => [['ulid', 'price', 'quantity', 'yes_fee', 'no_fee', 'created_at']]]);
});

it('filters trades by event_ulid', function () {
    createTrade($this);

    $otherEvent = Event::factory()->open()->create();

    $this->actingAs($this->admin, 'admins')
        ->getJson("/api/v1/admin/reports/trades?event_ulid={$otherEvent->ulid}")
        ->assertStatus(200)
        ->assertJsonCount(0, 'data');

    $this->actingAs($this->admin, 'admins')
        ->getJson("/api/v1/admin/reports/trades?event_ulid={$this->event->ulid}")
        ->assertStatus(200)
        ->assertJsonCount(1, 'data');
});

// ─── GET /api/v1/admin/reports/wallets ───────────────────────────────────────

it('returns a paginated list of all users with balances', function () {
    $this->actingAs($this->admin, 'admins')
        ->getJson('/api/v1/admin/reports/wallets')
        ->assertStatus(200)
        ->assertJsonStructure(['data' => [['ulid', 'name', 'email', 'balance']]]);

    // buyer and seller are present
    $response = $this->actingAs($this->admin, 'admins')
        ->getJson('/api/v1/admin/reports/wallets')
        ->assertStatus(200);

    $ulids = collect($response->json('data'))->pluck('ulid')->all();
    expect($ulids)->toContain($this->buyer->ulid);
    expect($ulids)->toContain($this->seller->ulid);
});

// ─── Auth guard ───────────────────────────────────────────────────────────────

it('returns 401 on report endpoints when unauthenticated', function () {
    $this->getJson('/api/v1/admin/reports/fees')->assertStatus(401);
    $this->getJson('/api/v1/admin/reports/trades')->assertStatus(401);
    $this->getJson('/api/v1/admin/reports/wallets')->assertStatus(401);
});
