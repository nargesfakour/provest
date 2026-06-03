<?php

declare(strict_types=1);

uses(Tests\TestCase::class);
uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

use App\Modules\Auth\Models\User;
use App\Modules\Wallet\Enums\WithdrawalStatus;
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

    $this->user = User::factory()->create();

    DB::table('users')->where('id', $this->user->id)->update(['balance' => '500.00000000']);
});

function fakeSuccessfulWithdrawal(string $abanTetherId = 'aban-wd-001'): void
{
    Http::fake([
        'api.abantether.com/transaction_bc/withdraw/request' => Http::response([
            'data' => ['id' => $abanTetherId, 'state' => 'PENDING'],
        ], 200),
    ]);
}

function fakeFailedWithdrawal(): void
{
    Http::fake([
        'api.abantether.com/transaction_bc/withdraw/request' => Http::response([], 500),
    ]);
}

// ─── POST /api/v1/wallet/withdraw ────────────────────────────────────────────

it('locks funds, creates withdrawal, and calls AbanTether on success', function () {
    fakeSuccessfulWithdrawal('aban-001');

    $response = $this->actingAs($this->user, 'users')
        ->postJson('/api/v1/wallet/withdraw', [
            'amount'              => '100',
            'network'             => 'TRC20',
            'destination_address' => 'TRxDestination123',
        ]);

    $response->assertStatus(201)
        ->assertJsonPath('amount', '100.00000000')
        ->assertJsonPath('network', 'TRC20')
        ->assertJsonPath('status', 'Processing');

    // Balance must be reduced by locked amount
    $this->assertDatabaseHas('users', [
        'id'      => $this->user->id,
        'balance' => '400.00000000',
    ]);

    // Withdrawal record created
    $this->assertDatabaseHas('withdrawals', [
        'user_id'        => $this->user->id,
        'amount'         => '100.00000000',
        'abantether_id'  => 'aban-001',
        'status'         => WithdrawalStatus::Processing->value,
    ]);

    // Lock ledger entry created
    $this->assertDatabaseHas('wallet_transactions', [
        'user_id'      => $this->user->id,
        'amount'       => '-100.00000000',
        'balance_after'=> '400.00000000',
    ]);
});

it('stays Pending and funds remain locked when AbanTether call fails', function () {
    fakeFailedWithdrawal();

    $response = $this->actingAs($this->user, 'users')
        ->postJson('/api/v1/wallet/withdraw', [
            'amount'              => '50',
            'network'             => 'TRC20',
            'destination_address' => 'TRxDestination456',
        ]);

    // Request succeeds from the user's perspective
    $response->assertStatus(201)
        ->assertJsonPath('status', 'Pending');

    // Balance is still locked
    $this->assertDatabaseHas('users', [
        'id'      => $this->user->id,
        'balance' => '450.00000000',
    ]);

    // Withdrawal record exists but no abantether_id
    $this->assertDatabaseHas('withdrawals', [
        'user_id'       => $this->user->id,
        'status'        => WithdrawalStatus::Pending->value,
        'abantether_id' => null,
    ]);
});

it('returns 422 when balance is insufficient', function () {
    fakeSuccessfulWithdrawal();

    $this->actingAs($this->user, 'users')
        ->postJson('/api/v1/wallet/withdraw', [
            'amount'              => '600',
            'network'             => 'TRC20',
            'destination_address' => 'TRxAddr',
        ])
        ->assertStatus(422)
        ->assertJsonFragment(['message' => 'Insufficient balance']);

    // Balance unchanged, no records created
    $this->assertDatabaseHas('users', ['id' => $this->user->id, 'balance' => '500.00000000']);
    $this->assertDatabaseCount('withdrawals', 0);
    $this->assertDatabaseCount('wallet_transactions', 0);
});

it('returns 422 when amount is below minimum', function () {
    fakeSuccessfulWithdrawal();

    $this->actingAs($this->user, 'users')
        ->postJson('/api/v1/wallet/withdraw', [
            'amount'              => '5',
            'network'             => 'TRC20',
            'destination_address' => 'TRxAddr',
        ])
        ->assertStatus(422);

    $this->assertDatabaseCount('withdrawals', 0);
});

it('returns 422 when amount is missing', function () {
    $this->actingAs($this->user, 'users')
        ->postJson('/api/v1/wallet/withdraw', [
            'network'             => 'TRC20',
            'destination_address' => 'TRxAddr',
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['amount']);
});

it('returns 422 when destination_address is missing', function () {
    $this->actingAs($this->user, 'users')
        ->postJson('/api/v1/wallet/withdraw', [
            'amount'  => '50',
            'network' => 'TRC20',
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['destination_address']);
});

it('returns 422 when network is invalid', function () {
    $this->actingAs($this->user, 'users')
        ->postJson('/api/v1/wallet/withdraw', [
            'amount'              => '50',
            'network'             => 'SOLANA',
            'destination_address' => 'SomeAddr',
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['network']);
});

it('sends memo to AbanTether when provided', function () {
    fakeSuccessfulWithdrawal('aban-memo-001');

    $this->actingAs($this->user, 'users')
        ->postJson('/api/v1/wallet/withdraw', [
            'amount'              => '20',
            'network'             => 'ERC20',
            'destination_address' => '0xDeadBeef',
            'memo'                => 'my-tag',
        ])
        ->assertStatus(201);

    Http::assertSent(function ($request) {
        return str_contains($request->url(), 'withdraw/request')
            && $request->data()['memo'] === 'my-tag';
    });
});

it('returns 401 when unauthenticated', function () {
    $this->postJson('/api/v1/wallet/withdraw', [
        'amount'              => '50',
        'network'             => 'TRC20',
        'destination_address' => 'TRxAddr',
    ])->assertStatus(401);
});

it('correctly applies withdrawal fee to the locked amount', function () {
    config(['wallet.withdrawal_fee' => '2.00000000']);

    fakeSuccessfulWithdrawal('aban-fee-001');

    $this->actingAs($this->user, 'users')
        ->postJson('/api/v1/wallet/withdraw', [
            'amount'              => '100',
            'network'             => 'TRC20',
            'destination_address' => 'TRxAddr',
        ])
        ->assertStatus(201);

    // amount(100) + fee(2) = 102 locked, balance 500 - 102 = 398
    $this->assertDatabaseHas('users', [
        'id'      => $this->user->id,
        'balance' => '398.00000000',
    ]);

    $this->assertDatabaseHas('withdrawals', [
        'amount' => '100.00000000',
        'fee'    => '2.00000000',
    ]);

    $this->assertDatabaseHas('wallet_transactions', [
        'amount'        => '-102.00000000',
        'balance_after' => '398.00000000',
    ]);
});
