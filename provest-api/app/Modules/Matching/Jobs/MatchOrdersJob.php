<?php

declare(strict_types=1);

namespace App\Modules\Matching\Jobs;

use App\Modules\Matching\Services\MatchingEngineInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class MatchOrdersJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly int $eventId,
        public readonly int $orderId,
    ) {}

    public function handle(MatchingEngineInterface $engine): void
    {
        $engine->match($this->eventId);
    }
}
