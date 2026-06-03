<?php

declare(strict_types=1);

namespace App\Modules\Wallet\Http\Controllers;

use App\Modules\AbanTether\Services\AbanTetherServiceInterface;
use App\Modules\Wallet\Http\Requests\DepositRequest;
use App\Modules\Wallet\Http\Requests\WithdrawalRequest;
use App\Modules\Wallet\Http\Resources\DepositResource;
use App\Modules\Wallet\Http\Resources\WalletTransactionResource;
use App\Modules\Wallet\Http\Resources\WithdrawalResource;
use App\Modules\Wallet\Services\DepositServiceInterface;
use App\Modules\Wallet\Services\WalletServiceInterface;
use App\Modules\Wallet\Services\WithdrawalServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;

class WalletController extends Controller
{
    public function __construct(
        private readonly WalletServiceInterface      $walletService,
        private readonly DepositServiceInterface     $depositService,
        private readonly WithdrawalServiceInterface  $withdrawalService,
        private readonly AbanTetherServiceInterface  $abanTether,
    ) {}

    public function balance(Request $request): JsonResponse
    {
        $user = $request->user('users');

        return response()->json([
            'balance' => $this->walletService->getBalance($user->id),
        ]);
    }

    public function transactions(Request $request): AnonymousResourceCollection
    {
        $user = $request->user('users');

        $txs = $this->walletService->transactions(
            userId:  $user->id,
            perPage: (int) $request->input('per_page', 20),
        );

        return WalletTransactionResource::collection($txs);
    }

    public function deposit(DepositRequest $request): JsonResponse
    {
        $user = $request->user('users');

        $deposit = $this->depositService->processDeposit(
            userId:  $user->id,
            txId:    $request->validated('tx_id'),
            network: $request->validated('network'),
        );

        return response()->json(new DepositResource($deposit), 201);
    }

    public function withdraw(WithdrawalRequest $request): JsonResponse
    {
        $user = $request->user('users');

        $withdrawal = $this->withdrawalService->requestWithdrawal(
            userId:             $user->id,
            amount:             bcmul((string) $request->validated('amount'), '1', 8),
            network:            $request->validated('network'),
            destinationAddress: $request->validated('destination_address'),
            memo:               $request->validated('memo'),
        );

        return response()->json(new WithdrawalResource($withdrawal), 201);
    }

    public function depositAddress(Request $request): JsonResponse
    {
        $network = strtoupper((string) $request->input('network', 'TRC20'));

        $address = $this->abanTether->getDepositAddress($network);

        return response()->json([
            'network' => $network,
            'coin'    => 'USDT',
            'address' => $address,
        ]);
    }

    public function withdrawalFee(Request $request): JsonResponse
    {
        $network = (string) $request->input('network', 'TRC20');

        return response()->json(
            $this->withdrawalService->getWithdrawalFee($network)
        );
    }
}
