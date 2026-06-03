<?php

declare(strict_types=1);

namespace App\Modules\Admin\Http\Controllers;

use App\Modules\Admin\Services\AdminDashboardServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

class AdminDashboardController extends Controller
{
    public function __construct(
        private readonly AdminDashboardServiceInterface $service,
    ) {}

    public function index(): JsonResponse
    {
        return response()->json($this->service->stats());
    }
}
