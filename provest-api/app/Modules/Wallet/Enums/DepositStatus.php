<?php

declare(strict_types=1);

namespace App\Modules\Wallet\Enums;

enum DepositStatus: int
{
    case Pending   = 0;
    case Confirmed = 1;
    case Rejected  = 2;
}
