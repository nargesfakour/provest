<?php

declare(strict_types=1);

namespace App\Modules\Positions\Http\Controllers;

use App\Modules\Positions\Http\Resources\PositionResource;
use App\Modules\Positions\Services\PositionServiceInterface;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;

class PositionController extends Controller
{
    public function __construct(
        private readonly PositionServiceInterface $service,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $positions = $this->service->paginateForUser(
            userId:  $request->user()->id,
            perPage: (int) $request->input('per_page', 20),
        );

        return PositionResource::collection($positions);
    }
}
