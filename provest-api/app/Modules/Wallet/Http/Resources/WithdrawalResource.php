<?php

declare(strict_types=1);

namespace App\Modules\Wallet\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WithdrawalResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'ulid'                => $this->ulid,
            'amount'              => $this->amount,
            'fee'                 => $this->fee,
            'coin'                => $this->coin,
            'network'             => $this->network,
            'destination_address' => $this->destination_address,
            'status'              => $this->status->name,
            'created_at'          => $this->created_at->toISOString(),
        ];
    }
}
