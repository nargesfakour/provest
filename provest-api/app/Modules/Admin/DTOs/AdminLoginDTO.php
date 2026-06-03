<?php

declare(strict_types=1);

namespace App\Modules\Admin\DTOs;

use App\Modules\Shared\DTOs\BaseDTO;

readonly class AdminLoginDTO extends BaseDTO
{
    public function __construct(
        public string $email,
        public string $password,
        public string $deviceName,
    ) {}
}
