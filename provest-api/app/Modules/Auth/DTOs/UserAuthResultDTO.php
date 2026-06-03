<?php

declare(strict_types=1);

namespace App\Modules\Auth\DTOs;

use App\Modules\Auth\Models\User;
use App\Modules\Shared\DTOs\BaseDTO;

readonly class UserAuthResultDTO extends BaseDTO
{
    public function __construct(
        public User   $user,
        public string $token,
    ) {}
}
