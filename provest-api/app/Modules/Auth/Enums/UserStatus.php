<?php

declare(strict_types=1);

namespace App\Modules\Auth\Enums;

enum UserStatus: int
{
    case PendingVerification = 0;
    case Active              = 1;
    case Suspended           = 2;
    case Banned              = 3;

    public function label(): string
    {
        return match($this) {
            self::PendingVerification => 'pending_verification',
            self::Active              => 'active',
            self::Suspended           => 'suspended',
            self::Banned              => 'banned',
        };
    }
}
