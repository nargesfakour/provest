<?php

declare(strict_types=1);

namespace App\Modules\Settlement\Events;

use App\Modules\Markets\Enums\EventOutcome;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class EventSettled implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly string       $eventUlid,
        public readonly EventOutcome $outcome,
    ) {}

    /** @return Channel[] */
    public function broadcastOn(): array
    {
        return [new Channel('event.' . $this->eventUlid)];
    }

    public function broadcastAs(): string
    {
        return 'event.settled';
    }

    public function broadcastWith(): array
    {
        return [
            'event_ulid' => $this->eventUlid,
            'outcome'    => $this->outcome->name,
        ];
    }
}
