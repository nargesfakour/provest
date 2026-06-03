<?php

declare(strict_types=1);

namespace App\Modules\Shared\DTOs;

use Illuminate\Pagination\LengthAwarePaginator;

readonly class PaginatedResultDTO
{
    public function __construct(
        public array $data,
        public int   $total,
        public int   $perPage,
        public int   $currentPage,
        public int   $lastPage,
    ) {}

    public static function fromPaginator(LengthAwarePaginator $paginator): self
    {
        return new self(
            data:        $paginator->items(),
            total:       $paginator->total(),
            perPage:     $paginator->perPage(),
            currentPage: $paginator->currentPage(),
            lastPage:    $paginator->lastPage(),
        );
    }
}
