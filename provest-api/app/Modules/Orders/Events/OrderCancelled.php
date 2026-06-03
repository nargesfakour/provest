<?php

declare(strict_types=1);

namespace App\Modules\Orders\Events;

use App\Modules\Orders\Models\Order;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderCancelled implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Order $order,
    ) {}

    /** @return Channel[] */
    public function broadcastOn(): array
    {
        $this->order->loadMissing(['event', 'user']);

        return [
            new Channel('event.' . $this->order->event->ulid),
            new PrivateChannel('user.' . $this->order->user->ulid),
        ];
    }

    public function broadcastAs(): string
    {
        return 'order.cancelled';
    }

    public function broadcastWith(): array
    {
        return [
            'ulid'       => $this->order->ulid,
            'status'     => $this->order->status->name,
            'event_ulid' => $this->order->event->ulid,
        ];
    }
}
