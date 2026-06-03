<?php

declare(strict_types=1);

uses(Tests\TestCase::class);

use App\Modules\AbanTether\DTOs\DepositVerificationDTO;
use App\Modules\AbanTether\DTOs\WithdrawalResponseDTO;
use App\Modules\AbanTether\Exceptions\AbanTetherException;
use App\Modules\AbanTether\Services\AbanTetherService;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config([
        'abantether.api_base'       => 'https://api.abantether.com',
        'abantether.api_token'      => 'test-token',
        'abantether.deposit_coin'   => 'USDT',
        'abantether.default_network' => 'TRC20',
    ]);

    $this->service = new AbanTetherService();
});

// ─── verifyDeposit ───────────────────────────────────────────────────────────

it('returns DepositVerificationDTO for a confirmed deposit', function () {
    Http::fake([
        'api.abantether.com/transaction_bc/list*' => Http::response([
            'data' => [
                'list' => [[
                    'tx_id'   => 'abc123',
                    'amount'  => '100.00000000',
                    'coin'    => 'USDT',
                    'network' => 'TRC20',
                    'state'   => 'DONE',
                ]],
            ],
        ], 200),
    ]);

    $dto = $this->service->verifyDeposit('abc123');

    expect($dto)->toBeInstanceOf(DepositVerificationDTO::class)
        ->and($dto->txId)->toBe('abc123')
        ->and($dto->amount)->toBe('100.00000000')
        ->and($dto->coin)->toBe('USDT')
        ->and($dto->network)->toBe('TRC20')
        ->and($dto->state)->toBe('DONE');
});

it('throws AbanTetherException when HTTP call fails', function () {
    Http::fake([
        'api.abantether.com/transaction_bc/list*' => Http::response([], 500),
    ]);

    $this->service->verifyDeposit('bad-tx');
})->throws(AbanTetherException::class, 'HTTP 500');

it('throws AbanTetherException when result list is empty', function () {
    Http::fake([
        'api.abantether.com/transaction_bc/list*' => Http::response([
            'data' => ['list' => []],
        ], 200),
    ]);

    $this->service->verifyDeposit('missing-tx');
})->throws(AbanTetherException::class, 'Transaction not found');

it('throws AbanTetherException when coin does not match', function () {
    Http::fake([
        'api.abantether.com/transaction_bc/list*' => Http::response([
            'data' => [
                'list' => [[
                    'tx_id'   => 'btctx',
                    'amount'  => '0.01000000',
                    'coin'    => 'BTC',
                    'network' => 'BTC',
                    'state'   => 'DONE',
                ]],
            ],
        ], 200),
    ]);

    $this->service->verifyDeposit('btctx');
})->throws(AbanTetherException::class, 'Invalid coin');

it('throws AbanTetherException when amount is zero', function () {
    Http::fake([
        'api.abantether.com/transaction_bc/list*' => Http::response([
            'data' => [
                'list' => [[
                    'tx_id'   => 'zerotx',
                    'amount'  => '0.00000000',
                    'coin'    => 'USDT',
                    'network' => 'TRC20',
                    'state'   => 'DONE',
                ]],
            ],
        ], 200),
    ]);

    $this->service->verifyDeposit('zerotx');
})->throws(AbanTetherException::class, 'amount must be positive');

it('throws AbanTetherException when state is not DONE', function () {
    Http::fake([
        'api.abantether.com/transaction_bc/list*' => Http::response([
            'data' => [
                'list' => [[
                    'tx_id'   => 'pendingtx',
                    'amount'  => '50.00000000',
                    'coin'    => 'USDT',
                    'network' => 'TRC20',
                    'state'   => 'PENDING',
                ]],
            ],
        ], 200),
    ]);

    $this->service->verifyDeposit('pendingtx');
})->throws(AbanTetherException::class, 'state is not DONE');

// ─── getDepositAddress ────────────────────────────────────────────────────────

it('returns deposit address for a given network', function () {
    Http::fake([
        'api.abantether.com/wallet/address*' => Http::response([
            'data' => ['address' => 'TRx1234567890ABCDEF'],
        ], 200),
    ]);

    $address = $this->service->getDepositAddress('TRC20');

    expect($address)->toBe('TRx1234567890ABCDEF');
});

it('throws AbanTetherException when address endpoint returns HTTP error', function () {
    Http::fake([
        'api.abantether.com/wallet/address*' => Http::response([], 401),
    ]);

    $this->service->getDepositAddress('TRC20');
})->throws(AbanTetherException::class, 'HTTP 401');

it('throws AbanTetherException when address is empty in response', function () {
    Http::fake([
        'api.abantether.com/wallet/address*' => Http::response([
            'data' => ['address' => ''],
        ], 200),
    ]);

    $this->service->getDepositAddress('TRC20');
})->throws(AbanTetherException::class, 'empty deposit address');

// ─── requestWithdrawal ────────────────────────────────────────────────────────

it('returns WithdrawalResponseDTO on successful withdrawal request', function () {
    Http::fake([
        'api.abantether.com/transaction_bc/withdraw/request' => Http::response([
            'data' => [
                'id'    => 'aban-withdraw-999',
                'state' => 'PENDING',
            ],
        ], 200),
    ]);

    $dto = $this->service->requestWithdrawal(
        network:     'TRC20',
        amount:      '200.00000000',
        destination: 'TRdestination123',
        memo:        null,
    );

    expect($dto)->toBeInstanceOf(WithdrawalResponseDTO::class)
        ->and($dto->abanTetherId)->toBe('aban-withdraw-999')
        ->and($dto->status)->toBe('PENDING');
});

it('sends memo in the payload when provided', function () {
    Http::fake([
        'api.abantether.com/transaction_bc/withdraw/request' => Http::response([
            'data' => ['id' => 'aban-555', 'state' => 'PENDING'],
        ], 200),
    ]);

    $this->service->requestWithdrawal(
        network:     'ERC20',
        amount:      '10.00000000',
        destination: '0xDeadBeef',
        memo:        'my-memo',
    );

    Http::assertSent(function ($request) {
        $body = $request->data();
        return isset($body['memo']) && $body['memo'] === 'my-memo';
    });
});

it('omits memo from payload when null', function () {
    Http::fake([
        'api.abantether.com/transaction_bc/withdraw/request' => Http::response([
            'data' => ['id' => 'aban-666', 'state' => 'PENDING'],
        ], 200),
    ]);

    $this->service->requestWithdrawal(
        network:     'TRC20',
        amount:      '10.00000000',
        destination: 'TRaddr',
        memo:        null,
    );

    Http::assertSent(function ($request) {
        $body = $request->data();
        return !array_key_exists('memo', $body);
    });
});

it('throws AbanTetherException when withdrawal request returns HTTP error', function () {
    Http::fake([
        'api.abantether.com/transaction_bc/withdraw/request' => Http::response([], 422),
    ]);

    $this->service->requestWithdrawal('TRC20', '50.00000000', 'TRaddr', null);
})->throws(AbanTetherException::class, 'HTTP 422');

it('throws AbanTetherException when withdrawal response has no ID', function () {
    Http::fake([
        'api.abantether.com/transaction_bc/withdraw/request' => Http::response([
            'data' => ['state' => 'PENDING'],
        ], 200),
    ]);

    $this->service->requestWithdrawal('TRC20', '50.00000000', 'TRaddr', null);
})->throws(AbanTetherException::class, 'did not return a withdrawal ID');
