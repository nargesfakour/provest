<?php

declare(strict_types=1);

namespace App\Modules\Settlement\Jobs;

use App\Modules\Markets\Enums\EventOutcome;
use App\Modules\Settlement\Services\SettlementServiceInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SettleEventJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly int          $eventId,
        public readonly EventOutcome $outcome,
    ) {}

    public function handle(SettlementServiceInterface $service): void
    {
        $service->settle($this->eventId, $this->outcome);
    }
}
