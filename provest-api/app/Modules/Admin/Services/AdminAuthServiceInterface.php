<?php

declare(strict_types=1);

namespace App\Modules\Admin\Services;

use App\Modules\Admin\DTOs\AdminAuthResultDTO;
use App\Modules\Admin\DTOs\AdminLoginDTO;
use App\Modules\Admin\Models\Admin;
use App\Modules\Shared\Contracts\ServiceInterface;

interface AdminAuthServiceInterface extends ServiceInterface
{
    public function login(AdminLoginDTO $dto): AdminAuthResultDTO;

    public function logout(Admin $admin): void;
}
