<?php

declare(strict_types=1);

namespace App\Modules\Auth\Services;

use App\Modules\Auth\DTOs\UserAuthResultDTO;
use App\Modules\Auth\DTOs\UserLoginDTO;
use App\Modules\Auth\DTOs\UserRegisterDTO;
use App\Modules\Auth\Enums\UserStatus;
use App\Modules\Auth\Models\User;
use App\Modules\Shared\Exceptions\BusinessException;
use Illuminate\Support\Facades\Hash;

class UserAuthService implements UserAuthServiceInterface
{
    public function register(UserRegisterDTO $dto): UserAuthResultDTO
    {
        $user = User::create([
            'name'     => $dto->name,
            'email'    => $dto->email,
            'password' => $dto->password,
        ]);

        $token = $user->createToken($dto->deviceName)->plainTextToken;

        return new UserAuthResultDTO(user: $user, token: $token);
    }

    public function login(UserLoginDTO $dto): UserAuthResultDTO
    {
        $user = User::where('email', $dto->email)->first();

        if (!$user || !Hash::check($dto->password, $user->password)) {
            throw new BusinessException('Invalid credentials.', 401);
        }

        if ($user->status !== UserStatus::Active) {
            throw new BusinessException('Your account is not active.', 403);
        }

        $token = $user->createToken($dto->deviceName)->plainTextToken;

        return new UserAuthResultDTO(user: $user, token: $token);
    }

    public function logout(User $user): void
    {
        $user->currentAccessToken()->delete();
    }
}
