<?php

declare(strict_types=1);

uses(Tests\TestCase::class);
uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

use App\Modules\Admin\Models\Admin;
use App\Modules\Auth\Models\User;
use App\Modules\Wallet\Enums\DepositStatus;
use App\Modules\Wallet\Models\Deposit;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config([
        'abantether.api_base'        => 'https://api.abantether.com',
        'abantether.api_token'       => 'test-token',
        'abantether.deposit_coin'    => 'USDT',
        'abantether.default_network' => 'TRC20',
    ]);

    $this->admin = Admin::factory()->create();
    $this->user  = User::factory()->create();
});

function makePendingDeposit(User $user): Deposit
{
    return Deposit::create([
        'user_id'  => $user->id,
        'tx_id'    => 'pending-tx-' . uniqid(),
        'amount'   => '100.00000000',
        'coin'     => 'USDT',
        'network'  => 'TRC20',
        'status'   => DepositStatus::Pending->value,
    ]);
}

function makeConfirmedDeposit(User $user): Deposit
{
    return Deposit::create([
        'user_id'      => $user->id,
        'tx_id'        => 'confirmed-tx-' . uniqid(),
        'amount'       => '200.00000000',
        'coin'         => 'USDT',
        'network'      => 'TRC20',
        'status'       => DepositStatus::Confirmed->value,
        'confirmed_at' => now(),
    ]);
}

// ─── GET /api/v1/admin/deposits ───────────────────────────────────────────────

it('lists all deposits for admin', function () {
    makePendingDeposit($this->user);
    makeConfirmedDeposit($this->user);

    $this->actingAs($this->admin, 'admins')
        ->getJson('/api/v1/admin/deposits')
        ->assertStatus(200)
        ->assertJsonCount(2, 'data');
});

it('filters deposits by status', function () {
    makePendingDeposit($this->user);
    makeConfirmedDeposit($this->user);

    $this->actingAs($this->admin, 'admins')
        ->getJson('/api/v1/admin/deposits?status=' . DepositStatus::Pending->value)
        ->assertStatus(200)
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.status', 'Pending');
});

it('returns 401 when unauthenticated on deposit list', function () {
    $this->getJson('/api/v1/admin/deposits')->assertStatus(401);
});

// ─── GET /api/v1/admin/deposits/{ulid} ────────────────────────────────────────

it('shows a single deposit', function () {
    $deposit = makePendingDeposit($this->user);

    $this->actingAs($this->admin, 'admins')
        ->getJson("/api/v1/admin/deposits/{$deposit->ulid}")
        ->assertStatus(200)
        ->assertJsonPath('ulid', $deposit->ulid)
        ->assertJsonPath('status', 'Pending');
});

it('returns 404 for unknown deposit ulid', function () {
    $this->actingAs($this->admin, 'admins')
        ->getJson('/api/v1/admin/deposits/01NONEXISTENT00000000000000')
        ->assertStatus(404);
});

// ─── PUT /api/v1/admin/deposits/{ulid}/reject ─────────────────────────────────

it('rejects a pending deposit with a reason', function () {
    $deposit = makePendingDeposit($this->user);

    $this->actingAs($this->admin, 'admins')
        ->putJson("/api/v1/admin/deposits/{$deposit->ulid}/reject", [
            'reason' => 'Fraudulent transaction',
        ])
        ->assertStatus(200)
        ->assertJsonPath('status', 'Rejected');

    $this->assertDatabaseHas('deposits', [
        'id'              => $deposit->id,
        'status'          => DepositStatus::Rejected->value,
        'rejected_reason' => 'Fraudulent transaction',
    ]);
});

it('returns 422 when trying to reject a confirmed deposit', function () {
    $deposit = makeConfirmedDeposit($this->user);

    $this->actingAs($this->admin, 'admins')
        ->putJson("/api/v1/admin/deposits/{$deposit->ulid}/reject", [
            'reason' => 'Too late',
        ])
        ->assertStatus(422);

    $this->assertDatabaseHas('deposits', [
        'id'     => $deposit->id,
        'status' => DepositStatus::Confirmed->value,
    ]);
});

it('returns 422 when reason is missing on reject', function () {
    $deposit = makePendingDeposit($this->user);

    $this->actingAs($this->admin, 'admins')
        ->putJson("/api/v1/admin/deposits/{$deposit->ulid}/reject", [])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['reason']);
});
