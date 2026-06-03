<?php

declare(strict_types=1);

namespace App\Modules\Wallet\Enums;

enum WalletTxType: int
{
    case Deposit    = 1;
    case Withdrawal = 2;
    case Lock       = 3;
    case Unlock     = 4;
    case Win        = 5;
    case Loss       = 6;
    case Fee        = 7;
}
