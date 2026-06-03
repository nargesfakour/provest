<?php

declare(strict_types=1);

namespace App\Modules\Auth\Http\Controllers;

use App\Modules\Auth\Http\Requests\LoginUserRequest;
use App\Modules\Auth\Http\Requests\RegisterUserRequest;
use App\Modules\Auth\Http\Resources\UserResource;
use App\Modules\Auth\Models\User;
use App\Modules\Auth\Services\UserAuthServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class UserAuthController extends Controller
{
    public function __construct(
        private readonly UserAuthServiceInterface $authService,
    ) {}

    public function register(RegisterUserRequest $request): JsonResponse
    {
        $result = $this->authService->register($request->toDTO());

        return response()->json([
            'user'  => new UserResource($result->user),
            'token' => $result->token,
        ], 201);
    }

    public function login(LoginUserRequest $request): JsonResponse
    {
        $result = $this->authService->login($request->toDTO());

        return response()->json([
            'user'  => new UserResource($result->user),
            'token' => $result->token,
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $this->authService->logout($user);

        return response()->json(['message' => 'Logged out.']);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'user' => new UserResource($request->user()),
        ]);
    }
}
