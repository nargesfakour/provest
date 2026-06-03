<?php

declare(strict_types=1);

uses(Tests\TestCase::class);
uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

use App\Modules\Auth\Models\User;
use App\Modules\Wallet\Enums\DepositStatus;
use App\Modules\Wallet\Enums\WalletTxType;
use App\Modules\Wallet\Models\Deposit;
use App\Modules\Wallet\Models\WalletTransaction;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config([
        'abantether.api_base'        => 'https://api.abantether.com',
        'abantether.api_token'       => 'test-token',
        'abantether.deposit_coin'    => 'USDT',
        'abantether.default_network' => 'TRC20',
    ]);

    $this->user = User::factory()->create();
});

function fakeConfirmedDeposit(string $txId, string $amount = '150.00000000'): void
{
    Http::fake([
        'api.abantether.com/transaction_bc/list*' => Http::response([
            'data' => [
                'list' => [[
                    'tx_id'   => $txId,
                    'amount'  => $amount,
                    'coin'    => 'USDT',
                    'network' => 'TRC20',
                    'state'   => 'DONE',
                ]],
            ],
        ], 200),
    ]);
}

// ─── POST /api/v1/wallet/deposit ─────────────────────────────────────────────

it('credits user balance and creates a deposit record on valid tx_id', function () {
    fakeConfirmedDeposit('tx-abc-001', '250.00000000');

    $response = $this->actingAs($this->user, 'users')
        ->postJson('/api/v1/wallet/deposit', [
            'tx_id'   => 'tx-abc-001',
            'network' => 'TRC20',
        ]);

    $response->assertStatus(201)
        ->assertJsonPath('ulid', fn ($v) => strlen($v) === 26)
        ->assertJsonPath('amount', '250.00000000')
        ->assertJsonPath('coin', 'USDT')
        ->assertJsonPath('network', 'TRC20')
        ->assertJsonPath('tx_id', 'tx-abc-001')
        ->assertJsonPath('status', 'Confirmed');

    $this->assertDatabaseHas('deposits', [
        'user_id' => $this->user->id,
        'tx_id'   => 'tx-abc-001',
        'amount'  => '250.00000000',
        'status'  => DepositStatus::Confirmed->value,
    ]);

    $this->assertDatabaseHas('wallet_transactions', [
        'user_id' => $this->user->id,
        'amount'  => '250.00000000',
    ]);

    // Balance must reflect the credited amount
    $this->assertDatabaseHas('users', [
        'id'      => $this->user->id,
        'balance' => '250.00000000',
    ]);
});

it('returns 409 when the same tx_id is submitted twice', function () {
    fakeConfirmedDeposit('tx-dup-001');

    $this->actingAs($this->user, 'users')
        ->postJson('/api/v1/wallet/deposit', ['tx_id' => 'tx-dup-001', 'network' => 'TRC20'])
        ->assertStatus(201);

    fakeConfirmedDeposit('tx-dup-001');

    $this->actingAs($this->user, 'users')
        ->postJson('/api/v1/wallet/deposit', ['tx_id' => 'tx-dup-001', 'network' => 'TRC20'])
        ->assertStatus(409);

    // Only one deposit and one wallet_transaction must exist
    $this->assertDatabaseCount('deposits', 1);
    $this->assertDatabaseCount('wallet_transactions', 1);
});

it('returns 422 when AbanTether cannot verify the transaction', function () {
    Http::fake([
        'api.abantether.com/transaction_bc/list*' => Http::response([
            'data' => ['list' => []],
        ], 200),
    ]);

    $this->actingAs($this->user, 'users')
        ->postJson('/api/v1/wallet/deposit', ['tx_id' => 'tx-bad-001', 'network' => 'TRC20'])
        ->assertStatus(422);

    $this->assertDatabaseCount('deposits', 0);
    $this->assertDatabaseCount('wallet_transactions', 0);
});

it('returns 422 when AbanTether API returns an HTTP error', function () {
    Http::fake([
        'api.abantether.com/transaction_bc/list*' => Http::response([], 500),
    ]);

    $this->actingAs($this->user, 'users')
        ->postJson('/api/v1/wallet/deposit', ['tx_id' => 'tx-err-001', 'network' => 'TRC20'])
        ->assertStatus(422);
});

it('returns 422 when tx_id is missing from request', function () {
    $this->actingAs($this->user, 'users')
        ->postJson('/api/v1/wallet/deposit', ['network' => 'TRC20'])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['tx_id']);
});

it('returns 422 when network is not one of the allowed values', function () {
    $this->actingAs($this->user, 'users')
        ->postJson('/api/v1/wallet/deposit', ['tx_id' => 'tx-001', 'network' => 'INVALID'])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['network']);
});

it('returns 401 when unauthenticated', function () {
    $this->postJson('/api/v1/wallet/deposit', ['tx_id' => 'tx-001', 'network' => 'TRC20'])
        ->assertStatus(401);
});

// ─── GET /api/v1/wallet/balance ───────────────────────────────────────────────

it('returns authenticated user balance', function () {
    \Illuminate\Support\Facades\DB::table('users')
        ->where('id', $this->user->id)
        ->update(['balance' => '99.50000000']);

    $this->actingAs($this->user, 'users')
        ->getJson('/api/v1/wallet/balance')
        ->assertStatus(200)
        ->assertJsonPath('balance', '99.50000000');
});

it('returns 401 on balance endpoint when unauthenticated', function () {
    $this->getJson('/api/v1/wallet/balance')->assertStatus(401);
});

// ─── GET /api/v1/wallet/transactions ─────────────────────────────────────────

it('returns paginated wallet transactions for authenticated user', function () {
    fakeConfirmedDeposit('tx-pg-001', '100.00000000');
    fakeConfirmedDeposit('tx-pg-002', '200.00000000');

    $this->actingAs($this->user, 'users')
        ->postJson('/api/v1/wallet/deposit', ['tx_id' => 'tx-pg-001', 'network' => 'TRC20']);

    Http::fake([
        'api.abantether.com/transaction_bc/list*' => Http::response([
            'data' => ['list' => [[
                'tx_id' => 'tx-pg-002', 'amount' => '200.00000000',
                'coin' => 'USDT', 'network' => 'TRC20', 'state' => 'DONE',
            ]]],
        ], 200),
    ]);

    $this->actingAs($this->user, 'users')
        ->postJson('/api/v1/wallet/deposit', ['tx_id' => 'tx-pg-002', 'network' => 'TRC20']);

    $response = $this->actingAs($this->user, 'users')
        ->getJson('/api/v1/wallet/transactions');

    $response->assertStatus(200)
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.type', 'Deposit');
});

it('does not expose another user transactions', function () {
    $other = User::factory()->create();

    fakeConfirmedDeposit('tx-other-001', '500.00000000');

    $this->actingAs($other, 'users')
        ->postJson('/api/v1/wallet/deposit', ['tx_id' => 'tx-other-001', 'network' => 'TRC20']);

    $response = $this->actingAs($this->user, 'users')
        ->getJson('/api/v1/wallet/transactions');

    $response->assertStatus(200)
        ->assertJsonCount(0, 'data');
});
