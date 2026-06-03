<?php

declare(strict_types=1);

uses(Tests\TestCase::class);
uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

use App\Modules\Auth\Models\User;
use App\Modules\Markets\Enums\EventStatus;
use App\Modules\Markets\Models\Event;
use App\Modules\Matching\Services\MatchingEngineInterface;
use App\Modules\Orders\Enums\OrderSide;
use App\Modules\Orders\Enums\OrderStatus;
use App\Modules\Orders\Models\Order;
use App\Modules\Positions\Models\Position;
use App\Modules\Trades\Models\Trade;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    Queue::fake(); // prevent MatchOrdersJob from running automatically during order placement

    $this->buyer  = User::factory()->create();
    $this->seller = User::factory()->create();
    $this->event  = Event::factory()->open()->create();

    DB::table('users')->where('id', $this->buyer->id)->update(['balance'  => '1000.00000000']);
    DB::table('users')->where('id', $this->seller->id)->update(['balance' => '1000.00000000']);
});

function order(mixed $ctx, mixed $user, mixed $event, string $side, string $price, string $qty, string $key): void
{
    $ctx->actingAs($user, 'users')->postJson('/api/v1/orders', [
        'event_ulid'      => $event->ulid,
        'side'            => $side,
        'price'           => $price,
        'quantity'        => $qty,
        'idempotency_key' => $key,
    ])->assertStatus(201);
}

// ─── Full match ───────────────────────────────────────────────────────────────

it('fully matches YES and NO orders at complementary prices', function () {
    order($this, $this->buyer,  $this->event, 'yes', '0.60', '10', 'full-yes-001');
    order($this, $this->seller, $this->event, 'no',  '0.40', '10', 'full-no-001');

    app(MatchingEngineInterface::class)->match($this->event->id);

    $this->assertDatabaseCount('trades', 1);

    $trade = Trade::first();
    expect($trade->quantity)->toBe('10.00000000');
    expect($trade->price)->toBe('0.6000');
    expect($trade->yes_fee)->toBe('0.10000000'); // 10 × 0.0100
    expect($trade->no_fee)->toBe('0.10000000');

    $yesOrder = Order::where('idempotency_key', 'full-yes-001')->first();
    $noOrder  = Order::where('idempotency_key', 'full-no-001')->first();

    expect($yesOrder->status)->toBe(OrderStatus::Filled);
    expect($yesOrder->filled_quantity)->toBe('10.00000000');
    expect($noOrder->status)->toBe(OrderStatus::Filled);
    expect($noOrder->filled_quantity)->toBe('10.00000000');
});

// ─── Positions ────────────────────────────────────────────────────────────────

it('creates positions for both sides after a match', function () {
    order($this, $this->buyer,  $this->event, 'yes', '0.60', '10', 'pos-yes-001');
    order($this, $this->seller, $this->event, 'no',  '0.40', '10', 'pos-no-001');

    app(MatchingEngineInterface::class)->match($this->event->id);

    $yesPos = Position::where('user_id', $this->buyer->id)
        ->where('event_id', $this->event->id)
        ->first();

    $noPos = Position::where('user_id', $this->seller->id)
        ->where('event_id', $this->event->id)
        ->first();

    expect($yesPos)->not->toBeNull();
    expect($yesPos->quantity)->toBe('10.00000000');
    expect($yesPos->avg_price)->toBe('0.60000000');
    expect($yesPos->side)->toBe(OrderSide::Yes);

    expect($noPos)->not->toBeNull();
    expect($noPos->quantity)->toBe('10.00000000');
    expect($noPos->avg_price)->toBe('0.40000000');
    expect($noPos->side)->toBe(OrderSide::No);
});

// ─── Partial match ────────────────────────────────────────────────────────────

it('partially fills the larger order when quantities differ', function () {
    order($this, $this->buyer,  $this->event, 'yes', '0.60', '10', 'partial-yes-001');
    order($this, $this->seller, $this->event, 'no',  '0.40', '6',  'partial-no-001');

    app(MatchingEngineInterface::class)->match($this->event->id);

    $this->assertDatabaseCount('trades', 1);
    expect(Trade::first()->quantity)->toBe('6.00000000');

    $yesOrder = Order::where('idempotency_key', 'partial-yes-001')->first();
    $noOrder  = Order::where('idempotency_key', 'partial-no-001')->first();

    expect($yesOrder->status)->toBe(OrderStatus::Partial);
    expect($yesOrder->filled_quantity)->toBe('6.00000000');
    expect($noOrder->status)->toBe(OrderStatus::Filled);
    expect($noOrder->filled_quantity)->toBe('6.00000000');
});

// ─── No match ─────────────────────────────────────────────────────────────────

it('creates no trade when prices do not sum to at least 1.0000', function () {
    order($this, $this->buyer,  $this->event, 'yes', '0.60', '5', 'nomatch-yes-001');
    order($this, $this->seller, $this->event, 'no',  '0.30', '5', 'nomatch-no-001'); // 0.60+0.30=0.90

    app(MatchingEngineInterface::class)->match($this->event->id);

    $this->assertDatabaseCount('trades', 0);
    expect(Order::where('idempotency_key', 'nomatch-yes-001')->first()->status)->toBe(OrderStatus::Open);
    expect(Order::where('idempotency_key', 'nomatch-no-001')->first()->status)->toBe(OrderStatus::Open);
});

// ─── Price priority ───────────────────────────────────────────────────────────

it('matches the highest-priced YES order first', function () {
    $buyer2 = User::factory()->create();
    DB::table('users')->where('id', $buyer2->id)->update(['balance' => '1000.00000000']);

    order($this, $this->buyer, $this->event, 'yes', '0.60', '5', 'prio-yes-low');  // placed first
    order($this, $buyer2,      $this->event, 'yes', '0.70', '5', 'prio-yes-high'); // higher price
    order($this, $this->seller, $this->event, 'no', '0.30', '5', 'prio-no-001');   // 0.70+0.30=1.00 matches; 0.60+0.30=0.90 does not

    app(MatchingEngineInterface::class)->match($this->event->id);

    $this->assertDatabaseCount('trades', 1);
    expect(Order::where('idempotency_key', 'prio-yes-high')->first()->status)->toBe(OrderStatus::Filled);
    expect(Order::where('idempotency_key', 'prio-yes-low')->first()->status)->toBe(OrderStatus::Open);
});

// ─── One YES against multiple NO orders ──────────────────────────────────────

it('matches one YES order against multiple NO orders and accumulates position', function () {
    $seller2 = User::factory()->create();
    DB::table('users')->where('id', $seller2->id)->update(['balance' => '1000.00000000']);

    order($this, $this->buyer,  $this->event, 'yes', '0.60', '10', 'multi-yes-001');
    order($this, $this->seller, $this->event, 'no',  '0.40', '4',  'multi-no-001');
    order($this, $seller2,      $this->event, 'no',  '0.40', '6',  'multi-no-002');

    app(MatchingEngineInterface::class)->match($this->event->id);

    $this->assertDatabaseCount('trades', 2);

    $yesOrder = Order::where('idempotency_key', 'multi-yes-001')->first();
    expect($yesOrder->status)->toBe(OrderStatus::Filled);
    expect($yesOrder->filled_quantity)->toBe('10.00000000');

    $yesPos = Position::where('user_id', $this->buyer->id)->where('event_id', $this->event->id)->first();
    expect($yesPos->quantity)->toBe('10.00000000');
    // avg_price = (4×0.60 + 6×0.60) / 10 = 0.60
    expect($yesPos->avg_price)->toBe('0.60000000');
});

// ─── Idempotency on re-run ────────────────────────────────────────────────────

it('is idempotent — running the engine twice does not create duplicate trades', function () {
    order($this, $this->buyer,  $this->event, 'yes', '0.60', '5', 'idem-yes-001');
    order($this, $this->seller, $this->event, 'no',  '0.40', '5', 'idem-no-001');

    $engine = app(MatchingEngineInterface::class);
    $engine->match($this->event->id);
    $engine->match($this->event->id); // second run — all orders already Filled

    $this->assertDatabaseCount('trades', 1);
});

// ─── Closed event guard ───────────────────────────────────────────────────────

it('does not match when the event is not Open', function () {
    order($this, $this->buyer,  $this->event, 'yes', '0.60', '5', 'closed-yes-001');
    order($this, $this->seller, $this->event, 'no',  '0.40', '5', 'closed-no-001');

    DB::table('events')
        ->where('id', $this->event->id)
        ->update(['status' => EventStatus::Closed->value]);

    app(MatchingEngineInterface::class)->match($this->event->id);

    $this->assertDatabaseCount('trades', 0);
});
