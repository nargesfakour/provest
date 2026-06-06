<?php

declare(strict_types=1);

namespace App\Modules\Admin\Http\Controllers;

use App\Modules\Admin\Http\Requests\CreateAdminRequest;
use App\Modules\Admin\Http\Resources\AdminResource;
use App\Modules\Admin\Services\AdminManagementServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

class AdminManagementController extends Controller
{
    public function __construct(
        private readonly AdminManagementServiceInterface $service,
    ) {}

    public function index(): JsonResponse
    {
        $paginator = $this->service->listAdmins(perPage: 15);

        return response()->json([
            'data' => AdminResource::collection($paginator->items()),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'total'        => $paginator->total(),
                'per_page'     => $paginator->perPage(),
            ],
        ]);
    }

    public function store(CreateAdminRequest $request): JsonResponse
    {
        $admin = $this->service->createAdmin($request->toDTO());

        return response()->json([
            'data'    => new AdminResource($admin),
            'message' => 'Admin created successfully.',
        ], 201);
    }
}
