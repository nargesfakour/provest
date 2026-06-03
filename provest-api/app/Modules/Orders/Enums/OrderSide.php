<?php

declare(strict_types=1);

namespace App\Modules\Orders\Enums;

enum OrderSide: int
{
    case Yes = 1;
    case No  = 2;
}
