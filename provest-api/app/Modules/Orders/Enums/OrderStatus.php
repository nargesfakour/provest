<?php

declare(strict_types=1);

namespace App\Modules\Orders\Enums;

enum OrderStatus: int
{
    case Open      = 0;
    case Partial   = 1;
    case Filled    = 2;
    case Cancelled = 3;
}
