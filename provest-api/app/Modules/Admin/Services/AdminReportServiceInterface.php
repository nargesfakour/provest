<?php

declare(strict_types=1);

namespace App\Modules\Admin\Services;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface AdminReportServiceInterface
{
    public function fees(array $filters): array;

    public function trades(array $filters, int $perPage): LengthAwarePaginator;

    public function wallets(int $perPage): LengthAwarePaginator;

    public function volume(array $filters): Collection;
}
