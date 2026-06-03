<?php

declare(strict_types=1);

namespace App\Modules\Admin\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'ulid'       => $this->ulid,
            'name'       => $this->name,
            'email'      => $this->email,
            'created_at' => $this->created_at,
        ];
    }
}
