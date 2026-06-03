<?php

declare(strict_types=1);

namespace App\Modules\Admin\Http\Resources;

use App\Modules\Auth\Enums\UserStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminUserResource extends JsonResource
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
            'updated_at' => $this->updated_at,
        ];
    }
}
