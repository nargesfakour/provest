<?php

declare(strict_types=1);

namespace App\Modules\Auth\Services;

use App\Modules\Auth\DTOs\UserAuthResultDTO;
use App\Modules\Auth\DTOs\UserLoginDTO;
use App\Modules\Auth\DTOs\UserRegisterDTO;
use App\Modules\Auth\Models\User;
use App\Modules\Shared\Contracts\ServiceInterface;

interface UserAuthServiceInterface extends ServiceInterface
{
    public function register(UserRegisterDTO $dto): UserAuthResultDTO;

    public function login(UserLoginDTO $dto): UserAuthResultDTO;

    public function logout(User $user): void;
}
