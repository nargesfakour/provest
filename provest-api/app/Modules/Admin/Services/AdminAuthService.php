<?php

declare(strict_types=1);

namespace App\Modules\Admin\Services;

use App\Modules\Admin\DTOs\AdminAuthResultDTO;
use App\Modules\Admin\DTOs\AdminLoginDTO;
use App\Modules\Admin\Models\Admin;
use App\Modules\Admin\Repositories\AdminRepositoryInterface;
use App\Modules\Shared\Exceptions\BusinessException;
use Illuminate\Support\Facades\Hash;

class AdminAuthService implements AdminAuthServiceInterface
{
    public function __construct(
        private readonly AdminRepositoryInterface $adminRepository,
    ) {}

    public function login(AdminLoginDTO $dto): AdminAuthResultDTO
    {
        $admin = $this->adminRepository->findByEmail($dto->email);

        if (!$admin || !Hash::check($dto->password, $admin->password)) {
            throw new BusinessException('Invalid credentials.', 401);
        }

        $token = $admin->createToken($dto->deviceName)->plainTextToken;

        return new AdminAuthResultDTO(admin: $admin, token: $token);
    }

    public function logout(Admin $admin): void
    {
        $admin->currentAccessToken()->delete();
    }
}
