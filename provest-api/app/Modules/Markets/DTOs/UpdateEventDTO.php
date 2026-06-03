<?php

declare(strict_types=1);

namespace App\Modules\Markets\DTOs;

use App\Modules\Shared\DTOs\BaseDTO;

readonly class UpdateEventDTO extends BaseDTO
{
    public function __construct(
        public string  $title,
        public ?string $description,
        public string  $subCategoryUlid,
        public string  $yesInitialPrice,
        public string  $feeRate,
        public ?string $startsAt,
        public ?string $endsAt,
        public ?string $resolveAt,
    ) {}
}
