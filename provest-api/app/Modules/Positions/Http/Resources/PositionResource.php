<?php

declare(strict_types=1);

namespace App\Modules\Positions\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PositionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            // Admin event-positions view loads 'user'; user self-view loads 'event'
            'user_ulid'   => $this->whenLoaded('user',  fn () => $this->user->ulid),
            'event_ulid'  => $this->whenLoaded('event', fn () => $this->event->ulid),
            'event_title' => $this->whenLoaded('event', fn () => $this->event->title),
            'side'        => $this->side->name,
            'quantity'    => $this->quantity,
            'avg_price'   => $this->avg_price,
            'updated_at'  => $this->updated_at,
        ];
    }
}
