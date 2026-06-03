<?php

declare(strict_types=1);

uses(Tests\TestCase::class);
uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

use App\Modules\Admin\Models\Admin;
use App\Modules\Auth\Models\User;
use App\Modules\Wallet\Enums\WithdrawalStatus;
use App\Modules\Wallet\Models\Withdrawal;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config([
        'abantether.api_base'        => 'https://api.abantether.com',
        'abantether.api_token'       => 'test-token',
        'abantether.deposit_coin'    => 'USDT',
        'abantether.default_network' => 'TRC20',
        'wallet.withdrawal_fee'      => '0.00000000',
        'wallet.min_withdrawal'      => '10.00000000',
    ]);

    $this->admin = Admin::factory()->create();
    $this->user  = User::factory()->create();

    DB::table('users')->where('id', $this->user->id)->update(['balance' => '500.00000000']);
});

function makePendingWithdrawal(User $user, string $amount = '100.00000000'): Withdrawal
{
    // Create withdrawal record directly (bypasses the service so we can test admin actions)
    $withdrawal = Withdrawal::create([
        'user_id'             => $user->id,
        'amount'              => $amount,
        'fee'                 => '0.00000000',
        'coin'                => 'USDT',
        'network'             => 'TRC20',
        'destination_address' => 'TRxTestAddress123',
        'memo'                => null,
        'status'              => WithdrawalStatus::Pending->value,
    ]);

    // Lock the funds manually to match the real flow
    DB::table('users')->where('id', $user->id)
        ->decrement('balance', (float) $amount);

    DB::table('wallet_transactions')->insert([
        'ulid'            => \Symfony\Component\Uid\Ulid::generate(),
        'user_id'         => $user->id,
        'amount'          => '-' . $amount,
        'type'            => \App\Modules\Wallet\Enums\WalletTxType::Lock->value,
        'reference_type'  => 'withdrawals',
        'reference_id'    => $withdrawal->id,
        'balance_after'   => bcsub('500.00000000', $amount, 8),
        'idempotency_key' => 'withdrawal-lock:' . $withdrawal->ulid,
        'created_at'      => now(),
    ]);

    return $withdrawal;
}

function fakeAbanTetherWithdrawalSuccess(string $id = 'aban-retry-001'): void
{
    Http::fake([
        'api.abantether.com/transaction_bc/withdraw/request' => Http::response([
            'data' => ['id' => $id, 'state' => 'PENDING'],
        ], 200),
    ]);
}

function fakeAbanTetherWithdrawalFailure(): void
{
    Http::fake([
        'api.abantether.com/transaction_bc/withdraw/request' => Http::response([], 500),
    ]);
}

// ─── GET /api/v1/admin/withdrawals ────────────────────────────────────────────

it('lists all withdrawals for admin', function () {
    makePendingWithdrawal($this->user, '50.00000000');
    makePendingWithdrawal($this->user, '30.00000000');

    $this->actingAs($this->admin, 'admins')
        ->getJson('/api/v1/admin/withdrawals')
        ->assertStatus(200)
        ->assertJsonCount(2, 'data');
});

it('filters withdrawals by status', function () {
    $w = makePendingWithdrawal($this->user, '50.00000000');
    Withdrawal::where('id', $w->id)->update(['status' => WithdrawalStatus::Processing->value]);

    makePendingWithdrawal($this->user, '30.00000000');

    $this->actingAs($this->admin, 'admins')
        ->getJson('/api/v1/admin/withdrawals?status=' . WithdrawalStatus::Pending->value)
        ->assertStatus(200)
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.status', 'Pending');
});

it('returns 401 when unauthenticated on withdrawal list', function () {
    $this->getJson('/api/v1/admin/withdrawals')->assertStatus(401);
});

// ─── GET /api/v1/admin/withdrawals/{ulid} ─────────────────────────────────────

it('shows a single withdrawal', function () {
    $w = makePendingWithdrawal($this->user);

    $this->actingAs($this->admin, 'admins')
        ->getJson("/api/v1/admin/withdrawals/{$w->ulid}")
        ->assertStatus(200)
        ->assertJsonPath('ulid', $w->ulid)
        ->assertJsonPath('status', 'Pending');
});

it('returns 404 for unknown withdrawal ulid', function () {
    $this->actingAs($this->admin, 'admins')
        ->getJson('/api/v1/admin/withdrawals/01NONEXISTENT00000000000000')
        ->assertStatus(404);
});

// ─── PUT /api/v1/admin/withdrawals/{ulid}/approve ─────────────────────────────

it('approves a pending withdrawal and calls AbanTether', function () {
    $w = makePendingWithdrawal($this->user, '100.00000000');
    fakeAbanTetherWithdrawalSuccess('aban-approve-001');

    $this->actingAs($this->admin, 'admins')
        ->putJson("/api/v1/admin/withdrawals/{$w->ulid}/approve")
        ->assertStatus(200)
        ->assertJsonPath('status', 'Processing')
        ->assertJsonPath('abantether_id', 'aban-approve-001');

    $this->assertDatabaseHas('withdrawals', [
        'id'            => $w->id,
        'status'        => WithdrawalStatus::Processing->value,
        'abantether_id' => 'aban-approve-001',
    ]);
});

it('returns 422 when AbanTether call fails during approve', function () {
    $w = makePendingWithdrawal($this->user);
    fakeAbanTetherWithdrawalFailure();

    $this->actingAs($this->admin, 'admins')
        ->putJson("/api/v1/admin/withdrawals/{$w->ulid}/approve")
        ->assertStatus(422);

    // Withdrawal must still be Pending
    $this->assertDatabaseHas('withdrawals', [
        'id'     => $w->id,
        'status' => WithdrawalStatus::Pending->value,
    ]);
});

it('returns 422 when approving a non-pending withdrawal', function () {
    $w = makePendingWithdrawal($this->user);
    Withdrawal::where('id', $w->id)->update(['status' => WithdrawalStatus::Processing->value]);

    fakeAbanTetherWithdrawalSuccess();

    $this->actingAs($this->admin, 'admins')
        ->putJson("/api/v1/admin/withdrawals/{$w->ulid}/approve")
        ->assertStatus(422);
});

// ─── PUT /api/v1/admin/withdrawals/{ulid}/reject ──────────────────────────────

it('rejects a pending withdrawal and unlocks funds', function () {
    $w = makePendingWithdrawal($this->user, '100.00000000');

    $this->actingAs($this->admin, 'admins')
        ->putJson("/api/v1/admin/withdrawals/{$w->ulid}/reject")
        ->assertStatus(200)
        ->assertJsonPath('status', 'Failed');

    $this->assertDatabaseHas('withdrawals', [
        'id'     => $w->id,
        'status' => WithdrawalStatus::Failed->value,
    ]);

    // Balance restored to 500 (400 locked, then +100 unlocked)
    $this->assertDatabaseHas('users', [
        'id'      => $this->user->id,
        'balance' => '500.00000000',
    ]);

    // Unlock ledger entry created
    $this->assertDatabaseHas('wallet_transactions', [
        'user_id' => $this->user->id,
        'amount'  => '100.00000000',
    ]);
});

it('returns 422 when rejecting a non-pending withdrawal', function () {
    $w = makePendingWithdrawal($this->user);
    Withdrawal::where('id', $w->id)->update(['status' => WithdrawalStatus::Processing->value]);

    $this->actingAs($this->admin, 'admins')
        ->putJson("/api/v1/admin/withdrawals/{$w->ulid}/reject")
        ->assertStatus(422);

    $this->assertDatabaseHas('withdrawals', [
        'id'     => $w->id,
        'status' => WithdrawalStatus::Processing->value,
    ]);
});
