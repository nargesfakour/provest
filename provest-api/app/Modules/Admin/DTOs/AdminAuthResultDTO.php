<?php

declare(strict_types=1);

namespace App\Modules\Admin\DTOs;

use App\Modules\Admin\Models\Admin;
use App\Modules\Shared\DTOs\BaseDTO;

readonly class AdminAuthResultDTO extends BaseDTO
{
    public function __construct(
        public Admin  $admin,
        public string $token,
    ) {}
}
