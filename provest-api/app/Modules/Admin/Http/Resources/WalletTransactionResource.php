<?php

declare(strict_types=1);

namespace App\Modules\Admin\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WalletTransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'ulid'           => $this->ulid,
            'amount'         => $this->amount,
            'type'           => $this->type->name,
            'reference_type' => $this->reference_type,
            'reference_id'   => $this->reference_id,
            'balance_after'  => $this->balance_after,
            'created_at'     => $this->created_at,
        ];
    }
}
