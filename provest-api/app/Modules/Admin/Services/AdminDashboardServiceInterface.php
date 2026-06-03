<?php

declare(strict_types=1);

namespace App\Modules\Admin\Services;

interface AdminDashboardServiceInterface
{
    public function stats(): array;
}
