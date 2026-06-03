<?php

declare(strict_types=1);

namespace App\Modules\Wallet\Enums;

enum WithdrawalStatus: int
{
    case Pending    = 0;
    case Processing = 1;
    case Done       = 2;
    case Failed     = 3;
}
