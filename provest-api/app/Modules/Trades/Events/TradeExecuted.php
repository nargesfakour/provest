<?php

declare(strict_types=1);

namespace App\Modules\Trades\Events;

use App\Modules\Trades\Models\Trade;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TradeExecuted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Trade $trade,
    ) {}

    /** @return Channel[] */
    public function broadcastOn(): array
    {
        $this->trade->loadMissing('event');

        return [new Channel('event.' . $this->trade->event->ulid)];
    }

    public function broadcastAs(): string
    {
        return 'trade.executed';
    }

    public function broadcastWith(): array
    {
        return [
            'ulid'       => $this->trade->ulid,
            'price'      => $this->trade->price,
            'quantity'   => $this->trade->quantity,
            'yes_fee'    => $this->trade->yes_fee,
            'no_fee'     => $this->trade->no_fee,
            'created_at' => $this->trade->created_at,
        ];
    }
}
