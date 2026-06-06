<?php

declare(strict_types=1);

namespace App\Modules\Admin\Services;

use App\Modules\Admin\DTOs\CreateAdminDTO;
use App\Modules\Admin\Models\Admin;
use App\Modules\Shared\Contracts\ServiceInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface AdminManagementServiceInterface extends ServiceInterface
{
    public function listAdmins(int $perPage): LengthAwarePaginator;

    public function createAdmin(CreateAdminDTO $dto): Admin;
}
