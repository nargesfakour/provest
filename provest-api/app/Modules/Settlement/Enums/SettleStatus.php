<?php

declare(strict_types=1);

namespace App\Modules\Settlement\Enums;

enum SettleStatus: int
{
    case Pending = 0;
    case Paid    = 1;
}
