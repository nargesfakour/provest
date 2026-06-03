<?php

declare(strict_types=1);

namespace App\Modules\Matching\Services;

interface MatchingEngineInterface
{
    public function match(int $eventId): void;
}
