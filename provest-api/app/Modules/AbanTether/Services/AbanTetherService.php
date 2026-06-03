<?php

declare(strict_types=1);

namespace App\Modules\AbanTether\Services;

use App\Modules\AbanTether\DTOs\DepositVerificationDTO;
use App\Modules\AbanTether\DTOs\WithdrawalResponseDTO;
use App\Modules\AbanTether\Exceptions\AbanTetherException;
use Illuminate\Support\Facades\Http;

class AbanTetherService implements AbanTetherServiceInterface
{
    private string $baseUrl;
    private string $token;
    private string $depositCoin;

    public function __construct()
    {
        $this->baseUrl     = rtrim((string) config('abantether.api_base'), '/');
        $this->token       = (string) config('abantether.api_token');
        $this->depositCoin = (string) config('abantether.deposit_coin');
    }

    public function verifyDeposit(string $txId): DepositVerificationDTO
    {
        $response = Http::withToken($this->token)
            ->get("{$this->baseUrl}/transaction_bc/list", [
                'type'   => 'DEPOSIT',
                'tx_id'  => $txId,
                'state'  => 'DONE',
            ]);

        if ($response->failed()) {
            throw new AbanTetherException(
                "AbanTether deposit verification failed: HTTP {$response->status()}"
            );
        }

        $data = $response->json();

        $results = $data['data']['list'] ?? $data['results'] ?? null;

        if (empty($results) || !is_array($results)) {
            throw new AbanTetherException("Transaction not found or not confirmed: {$txId}");
        }

        $tx = $results[0];

        if (strtoupper((string) ($tx['coin'] ?? '')) !== strtoupper($this->depositCoin)) {
            throw new AbanTetherException(
                "Invalid coin: expected {$this->depositCoin}, got {$tx['coin']}"
            );
        }

        $amount = (string) ($tx['amount'] ?? '0');

        if (bccomp($amount, '0', 8) <= 0) {
            throw new AbanTetherException("Transaction amount must be positive, got: {$amount}");
        }

        if (strtoupper((string) ($tx['state'] ?? '')) !== 'DONE') {
            throw new AbanTetherException("Transaction state is not DONE: {$tx['state']}");
        }

        return new DepositVerificationDTO(
            txId:    (string) ($tx['tx_id']  ?? $txId),
            amount:  $amount,
            coin:    (string) ($tx['coin']    ?? $this->depositCoin),
            network: (string) ($tx['network'] ?? ''),
            state:   (string) ($tx['state']   ?? 'DONE'),
        );
    }

    public function getDepositAddress(string $network = 'TRC20'): string
    {
        $response = Http::withToken($this->token)
            ->get("{$this->baseUrl}/wallet/address", [
                'coin'    => $this->depositCoin,
                'network' => $network,
            ]);

        if ($response->failed()) {
            throw new AbanTetherException(
                "AbanTether get deposit address failed: HTTP {$response->status()}"
            );
        }

        $data    = $response->json();
        $address = $data['data']['address'] ?? $data['address'] ?? null;

        if (empty($address)) {
            throw new AbanTetherException('AbanTether returned empty deposit address');
        }

        return (string) $address;
    }

    public function requestWithdrawal(
        string  $network,
        string  $amount,
        string  $destination,
        ?string $memo,
    ): WithdrawalResponseDTO {
        $payload = [
            'coin'    => $this->depositCoin,
            'network' => $network,
            'amount'  => $amount,
            'address' => $destination,
        ];

        if ($memo !== null) {
            $payload['memo'] = $memo;
        }

        $response = Http::withToken($this->token)
            ->post("{$this->baseUrl}/transaction_bc/withdraw/request", $payload);

        if ($response->failed()) {
            throw new AbanTetherException(
                "AbanTether withdrawal request failed: HTTP {$response->status()}"
            );
        }

        $data = $response->json();

        $id = $data['data']['id'] ?? $data['id'] ?? null;

        if (empty($id)) {
            throw new AbanTetherException('AbanTether did not return a withdrawal ID');
        }

        $status = $data['data']['state'] ?? $data['state'] ?? 'PENDING';

        return new WithdrawalResponseDTO(
            abanTetherId: (string) $id,
            status:       (string) $status,
        );
    }
}
