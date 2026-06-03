<?php

declare(strict_types=1);

namespace App\Modules\Admin\Http\Controllers;

use App\Modules\Admin\Http\Requests\AdminLoginRequest;
use App\Modules\Admin\Http\Resources\AdminResource;
use App\Modules\Admin\Models\Admin;
use App\Modules\Admin\Services\AdminAuthServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class AdminAuthController extends Controller
{
    public function __construct(
        private readonly AdminAuthServiceInterface $authService,
    ) {}

    public function login(AdminLoginRequest $request): JsonResponse
    {
        $result = $this->authService->login($request->toDTO());

        return response()->json([
            'admin' => new AdminResource($result->admin),
            'token' => $result->token,
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        /** @var Admin $admin */
        $admin = $request->user();
        $this->authService->logout($admin);

        return response()->json(['message' => 'Logged out.']);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'admin' => new AdminResource($request->user()),
        ]);
    }
}
