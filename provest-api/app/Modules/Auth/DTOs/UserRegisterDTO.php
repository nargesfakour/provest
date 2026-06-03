<?php

declare(strict_types=1);

namespace App\Modules\Auth\DTOs;

use App\Modules\Shared\DTOs\BaseDTO;

readonly class UserRegisterDTO extends BaseDTO
{
    public function __construct(
        public string $name,
        public string $email,
        public string $password,
        public string $deviceName,
    ) {}
}
