<?php

declare(strict_types=1);

uses(Tests\TestCase::class);
uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

use App\Modules\Auth\Models\User;
use App\Modules\Markets\Models\Event;
use App\Modules\Matching\Services\MatchingEngineInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    Queue::fake();

    $this->user   = User::factory()->create();
    $this->seller = User::factory()->create();
    $this->event  = Event::factory()->open()->create();

    DB::table('users')->where('id', $this->user->id)->update(['balance'   => '1000.00000000']);
    DB::table('users')->where('id', $this->seller->id)->update(['balance' => '1000.00000000']);
});

it('returns an empty list when the user has no positions', function () {
    $this->actingAs($this->user, 'users')
        ->getJson('/api/v1/positions')
        ->assertStatus(200)
        ->assertJsonCount(0, 'data');
});

it('lists positions created after a match', function () {
    $this->actingAs($this->user, 'users')->postJson('/api/v1/orders', [
        'event_ulid'      => $this->event->ulid,
        'side'            => 'yes',
        'price'           => '0.60',
        'quantity'        => '5',
        'idempotency_key' => 'pos-list-yes-001',
    ]);

    $this->actingAs($this->seller, 'users')->postJson('/api/v1/orders', [
        'event_ulid'      => $this->event->ulid,
        'side'            => 'no',
        'price'           => '0.40',
        'quantity'        => '5',
        'idempotency_key' => 'pos-list-no-001',
    ]);

    app(MatchingEngineInterface::class)->match($this->event->id);

    $response = $this->actingAs($this->user, 'users')
        ->getJson('/api/v1/positions')
        ->assertStatus(200)
        ->assertJsonCount(1, 'data');

    $response->assertJsonPath('data.0.side', 'Yes');
    $response->assertJsonPath('data.0.quantity', '5.00000000');
    $response->assertJsonPath('data.0.avg_price', '0.60000000');
    $response->assertJsonPath('data.0.event_ulid', $this->event->ulid);
    $response->assertJsonPath('data.0.event_title', $this->event->title);
});

it('does not expose another user positions', function () {
    $this->actingAs($this->user, 'users')->postJson('/api/v1/orders', [
        'event_ulid'      => $this->event->ulid,
        'side'            => 'yes',
        'price'           => '0.60',
        'quantity'        => '5',
        'idempotency_key' => 'pos-other-yes-001',
    ]);

    $this->actingAs($this->seller, 'users')->postJson('/api/v1/orders', [
        'event_ulid'      => $this->event->ulid,
        'side'            => 'no',
        'price'           => '0.40',
        'quantity'        => '5',
        'idempotency_key' => 'pos-other-no-001',
    ]);

    app(MatchingEngineInterface::class)->match($this->event->id);

    // seller has a NO position; user should not see it
    $this->actingAs($this->seller, 'users')
        ->getJson('/api/v1/positions')
        ->assertStatus(200)
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.side', 'No');

    $this->actingAs($this->user, 'users')
        ->getJson('/api/v1/positions')
        ->assertStatus(200)
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.side', 'Yes');
});

it('returns 401 when unauthenticated', function () {
    $this->getJson('/api/v1/positions')->assertStatus(401);
});
