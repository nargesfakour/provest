<?php

declare(strict_types=1);

namespace App\Modules\Admin\Services;

use App\Modules\Admin\DTOs\CreateAdminDTO;
use App\Modules\Admin\Models\Admin;
use App\Modules\Admin\Repositories\AdminRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class AdminManagementService implements AdminManagementServiceInterface
{
    public function __construct(
        private readonly AdminRepositoryInterface $adminRepository,
    ) {}

    public function listAdmins(int $perPage): LengthAwarePaginator
    {
        return $this->adminRepository->paginate($perPage);
    }

    public function createAdmin(CreateAdminDTO $dto): Admin
    {
        /** @var Admin */
        return $this->adminRepository->create([
            'name'     => $dto->name,
            'email'    => $dto->email,
            'password' => $dto->password,
        ]);
    }
}
