<?php

declare(strict_types=1);

namespace App\Modules\Admin\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminDepositResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'ulid'            => $this->ulid,
            'user'            => [
                'ulid'  => $this->user?->ulid,
                'name'  => $this->user?->name,
                'email' => $this->user?->email,
            ],
            'tx_id'           => $this->tx_id,
            'amount'          => $this->amount,
            'coin'            => $this->coin,
            'network'         => $this->network,
            'status'          => $this->status->name,
            'rejected_reason' => $this->rejected_reason,
            'confirmed_at'    => $this->confirmed_at?->toISOString(),
            'created_at'      => $this->created_at->toISOString(),
        ];
    }
}
