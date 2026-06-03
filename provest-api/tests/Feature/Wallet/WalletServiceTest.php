<?php

declare(strict_types=1);

uses(Tests\TestCase::class);
uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

use App\Modules\Auth\Models\User;
use App\Modules\Shared\Exceptions\BusinessException;
use App\Modules\Wallet\Enums\WalletTxType;
use App\Modules\Wallet\Models\WalletTransaction;
use App\Modules\Wallet\Services\WalletServiceInterface;
use Illuminate\Support\Facades\DB;

function setBalance(User $user, string $amount): void
{
    DB::table('users')->where('id', $user->id)->update(['balance' => $amount]);
}

beforeEach(function () {
    $this->service = app(WalletServiceInterface::class);
    $this->user    = User::factory()->create();
});

// ─── credit ──────────────────────────────────────────────────────────────────

it('credits user balance and creates a ledger entry', function () {
    $tx = $this->service->credit(
        userId:         $this->user->id,
        amount:         '100.00000000',
        type:           WalletTxType::Deposit,
        referenceType:  null,
        referenceId:    null,
        idempotencyKey: 'credit-test-1',
    );

    expect($tx)->toBeInstanceOf(WalletTransaction::class)
        ->and($tx->amount)->toBe('100.00000000')
        ->and($tx->type)->toBe(WalletTxType::Deposit)
        ->and($tx->balance_after)->toBe('100.00000000');

    expect($this->service->getBalance($this->user->id))->toBe('100.00000000');

    $this->assertDatabaseHas('wallet_transactions', [
        'user_id'         => $this->user->id,
        'amount'          => '100.00000000',
        'balance_after'   => '100.00000000',
        'idempotency_key' => 'credit-test-1',
    ]);
});

it('is idempotent — duplicate credit key returns existing tx without double-crediting', function () {
    $tx1 = $this->service->credit(
        userId:         $this->user->id,
        amount:         '50.00000000',
        type:           WalletTxType::Deposit,
        referenceType:  null,
        referenceId:    null,
        idempotencyKey: 'idem-credit-1',
    );

    $tx2 = $this->service->credit(
        userId:         $this->user->id,
        amount:         '50.00000000',
        type:           WalletTxType::Deposit,
        referenceType:  null,
        referenceId:    null,
        idempotencyKey: 'idem-credit-1',
    );

    expect($tx1->id)->toBe($tx2->id);
    expect($this->service->getBalance($this->user->id))->toBe('50.00000000');
    $this->assertDatabaseCount('wallet_transactions', 1);
});

it('accumulates balance across multiple credits', function () {
    $this->service->credit($this->user->id, '30.00000000', WalletTxType::Deposit, null, null, 'credit-a');
    $this->service->credit($this->user->id, '20.50000000', WalletTxType::Win,     null, null, 'credit-b');

    expect($this->service->getBalance($this->user->id))->toBe('50.50000000');
    $this->assertDatabaseCount('wallet_transactions', 2);
});

// ─── debit ───────────────────────────────────────────────────────────────────

it('debits user balance and records a negative ledger entry', function () {
    setBalance($this->user, '200.00000000');

    $tx = $this->service->debit(
        userId:         $this->user->id,
        amount:         '75.00000000',
        type:           WalletTxType::Withdrawal,
        referenceType:  null,
        referenceId:    null,
        idempotencyKey: 'debit-test-1',
    );

    expect($tx->amount)->toBe('-75.00000000')
        ->and($tx->balance_after)->toBe('125.00000000');

    expect($this->service->getBalance($this->user->id))->toBe('125.00000000');
});

it('throws BusinessException when balance is insufficient for debit', function () {
    setBalance($this->user, '10.00000000');

    $this->service->debit(
        userId:         $this->user->id,
        amount:         '50.00000000',
        type:           WalletTxType::Withdrawal,
        referenceType:  null,
        referenceId:    null,
        idempotencyKey: 'debit-fail-1',
    );
})->throws(BusinessException::class, 'Insufficient balance');

it('does not create a ledger entry when debit fails due to insufficient balance', function () {
    setBalance($this->user, '5.00000000');

    try {
        $this->service->debit($this->user->id, '100.00000000', WalletTxType::Withdrawal, null, null, 'debit-no-tx');
    } catch (BusinessException) {}

    $this->assertDatabaseCount('wallet_transactions', 0);
    expect($this->service->getBalance($this->user->id))->toBe('5.00000000');
});

it('is idempotent — duplicate debit key returns existing tx without double-debiting', function () {
    setBalance($this->user, '100.00000000');

    $tx1 = $this->service->debit($this->user->id, '40.00000000', WalletTxType::Withdrawal, null, null, 'idem-debit-1');
    $tx2 = $this->service->debit($this->user->id, '40.00000000', WalletTxType::Withdrawal, null, null, 'idem-debit-1');

    expect($tx1->id)->toBe($tx2->id);
    expect($this->service->getBalance($this->user->id))->toBe('60.00000000');
    $this->assertDatabaseCount('wallet_transactions', 1);
});

// ─── lock ────────────────────────────────────────────────────────────────────

it('locks funds by reducing balance with WalletTxType::Lock', function () {
    setBalance($this->user, '100.00000000');

    $tx = $this->service->lock(
        userId:         $this->user->id,
        amount:         '30.00000000',
        referenceType:  'orders',
        referenceId:    42,
        idempotencyKey: 'lock-order-42',
    );

    expect($tx->type)->toBe(WalletTxType::Lock)
        ->and($tx->amount)->toBe('-30.00000000')
        ->and($tx->balance_after)->toBe('70.00000000')
        ->and($tx->reference_type)->toBe('orders')
        ->and($tx->reference_id)->toBe(42);

    expect($this->service->getBalance($this->user->id))->toBe('70.00000000');
});

it('throws BusinessException when locking more than available balance', function () {
    setBalance($this->user, '10.00000000');

    $this->service->lock($this->user->id, '50.00000000', 'orders', 1, 'lock-fail-1');
})->throws(BusinessException::class, 'Insufficient balance');

// ─── unlock ──────────────────────────────────────────────────────────────────

it('unlocks funds by restoring balance with WalletTxType::Unlock', function () {
    setBalance($this->user, '70.00000000');

    $tx = $this->service->unlock(
        userId:         $this->user->id,
        amount:         '30.00000000',
        referenceType:  'orders',
        referenceId:    42,
        idempotencyKey: 'unlock-order-42',
    );

    expect($tx->type)->toBe(WalletTxType::Unlock)
        ->and($tx->amount)->toBe('30.00000000')
        ->and($tx->balance_after)->toBe('100.00000000');

    expect($this->service->getBalance($this->user->id))->toBe('100.00000000');
});

// ─── getBalance ───────────────────────────────────────────────────────────────

it('returns user balance as string', function () {
    setBalance($this->user, '123.45000000');

    expect($this->service->getBalance($this->user->id))->toBe('123.45000000');
});

// ─── transactions ─────────────────────────────────────────────────────────────

it('paginates wallet transactions for a user', function () {
    $this->service->credit($this->user->id, '100.00000000', WalletTxType::Deposit, null, null, 'pg-1');
    $this->service->credit($this->user->id, '200.00000000', WalletTxType::Deposit, null, null, 'pg-2');

    $result = $this->service->transactions($this->user->id, 10);

    expect($result->total())->toBe(2)
        ->and($result->items())->toHaveCount(2);
});

it('does not return transactions belonging to another user', function () {
    $other = User::factory()->create();

    $this->service->credit($other->id, '500.00000000', WalletTxType::Deposit, null, null, 'other-tx');

    $result = $this->service->transactions($this->user->id, 10);

    expect($result->total())->toBe(0);
});
