<?php

declare(strict_types=1);

namespace App\Modules\Trades\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TradeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'ulid'         => $this->ulid,
            'yes_user_ulid' => $this->yesUser->ulid,
            'no_user_ulid'  => $this->noUser->ulid,
            'price'        => $this->price,
            'quantity'     => $this->quantity,
            'yes_fee'      => $this->yes_fee,
            'no_fee'       => $this->no_fee,
            'created_at'   => $this->created_at,
        ];
    }
}
