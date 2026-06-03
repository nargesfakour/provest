<?php

declare(strict_types=1);

namespace App\Modules\Markets\Enums;

enum EventStatus: int
{
    case Pending  = 1;
    case Open     = 2;
    case Closed   = 3;
    case Settled  = 4;
}
