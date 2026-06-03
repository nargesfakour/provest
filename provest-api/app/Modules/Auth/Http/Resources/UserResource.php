<?php

declare(strict_types=1);

namespace App\Modules\Auth\Http\Resources;

use App\Modules\Auth\Enums\UserStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'ulid'       => $this->ulid,
            'name'       => $this->name,
            'email'      => $this->email,
            'balance'    => $this->balance,
            'status'     => $this->status instanceof UserStatus ? $this->status->label() : null,
            'created_at' => $this->created_at,
        ];
    }
}
