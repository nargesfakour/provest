<?php

declare(strict_types=1);

namespace App\Modules\Markets\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'ulid'           => $this->ulid,
            'name'           => $this->name,
            'slug'           => $this->slug,
            'is_active'      => $this->is_active,
            'sub_categories' => $this->whenLoaded('subCategories', fn () =>
                $this->subCategories->map(fn ($sub) => [
                    'ulid'      => $sub->ulid,
                    'name'      => $sub->name,
                    'slug'      => $sub->slug,
                    'is_active' => $sub->is_active,
                ])->values()
            ),
            'created_at'     => $this->created_at,
            'updated_at'     => $this->updated_at,
        ];
    }
}
