<?php

declare(strict_types=1);

namespace App\Modules\Wallet\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DepositResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'ulid'            => $this->ulid,
            'amount'          => $this->amount,
            'coin'            => $this->coin,
            'network'         => $this->network,
            'tx_id'           => $this->tx_id,
            'status'          => $this->status->name,
            'confirmed_at'    => $this->confirmed_at?->toISOString(),
            'created_at'      => $this->created_at->toISOString(),
        ];
    }
}
