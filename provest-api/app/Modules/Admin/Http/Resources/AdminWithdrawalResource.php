<?php

declare(strict_types=1);

namespace App\Modules\Admin\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminWithdrawalResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'ulid'                => $this->ulid,
            'user'                => [
                'ulid'  => $this->user?->ulid,
                'name'  => $this->user?->name,
                'email' => $this->user?->email,
            ],
            'amount'              => $this->amount,
            'fee'                 => $this->fee,
            'coin'                => $this->coin,
            'network'             => $this->network,
            'destination_address' => $this->destination_address,
            'memo'                => $this->memo,
            'status'              => $this->status->name,
            'abantether_id'       => $this->abantether_id,
            'tx_id'               => $this->tx_id,
            'processed_at'        => $this->processed_at?->toISOString(),
            'created_at'          => $this->created_at->toISOString(),
        ];
    }
}
