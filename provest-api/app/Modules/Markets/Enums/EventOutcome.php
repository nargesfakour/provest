<?php

declare(strict_types=1);

namespace App\Modules\Markets\Enums;

enum EventOutcome: int
{
    case Yes = 1;
    case No  = 2;
}
