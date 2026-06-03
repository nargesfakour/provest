<?php

declare(strict_types=1);

namespace App\Modules\Markets\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubCategoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'ulid'          => $this->ulid,
            'category_ulid' => $this->category->ulid,
            'name'          => $this->name,
            'slug'          => $this->slug,
            'is_active'     => $this->is_active,
            'created_at'    => $this->created_at,
            'updated_at'    => $this->updated_at,
        ];
    }
}
