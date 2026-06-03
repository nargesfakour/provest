<?php

declare(strict_types=1);

namespace App\Modules\Markets\DTOs;

use App\Modules\Shared\DTOs\BaseDTO;

readonly class UpdateCategoryDTO extends BaseDTO
{
    public function __construct(
        public string $name,
        public string $slug,
        public bool   $isActive,
    ) {}
}
