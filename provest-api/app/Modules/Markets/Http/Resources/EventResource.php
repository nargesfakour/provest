<?php

declare(strict_types=1);

namespace App\Modules\Markets\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EventResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'ulid'              => $this->ulid,
            'title'             => $this->title,
            'description'       => $this->description,
            'status'            => $this->status->name,
            'outcome'           => $this->outcome?->name,
            'sub_category'      => [
                'ulid'     => $this->subCategory->ulid,
                'name'     => $this->subCategory->name,
                'category' => [
                    'ulid' => $this->subCategory->category->ulid,
                    'name' => $this->subCategory->category->name,
                ],
            ],
            'yes_initial_price' => $this->yes_initial_price,
            'no_initial_price'  => $this->no_initial_price,
            'fee_rate'          => $this->fee_rate,
            'starts_at'         => $this->starts_at,
            'ends_at'           => $this->ends_at,
            'resolve_at'        => $this->resolve_at,
            'created_at'        => $this->created_at,
            'updated_at'        => $this->updated_at,
        ];
    }
}
