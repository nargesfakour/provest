<?php

declare(strict_types=1);

namespace App\Modules\Orders\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'ulid'            => $this->ulid,
            'event_ulid'      => $this->event?->ulid,
            'side'            => $this->side->name,
            'price'           => $this->price,
            'quantity'        => $this->quantity,
            'filled_quantity' => $this->filled_quantity,
            'status'          => $this->status->name,
            'created_at'      => $this->created_at->toISOString(),
        ];
    }
}
