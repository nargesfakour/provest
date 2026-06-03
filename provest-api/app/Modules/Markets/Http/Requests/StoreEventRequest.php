<?php

declare(strict_types=1);

namespace App\Modules\Markets\Http\Requests;

use App\Modules\Markets\DTOs\CreateEventDTO;
use Illuminate\Foundation\Http\FormRequest;

class StoreEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title'             => ['required', 'string', 'max:500'],
            'description'       => ['sometimes', 'nullable', 'string'],
            'sub_category_ulid' => ['required', 'string'],
            'yes_initial_price' => ['required', 'numeric', 'between:0.01,0.99'],
            'fee_rate'          => ['sometimes', 'numeric', 'between:0,0.10'],
            'starts_at'         => ['sometimes', 'nullable', 'date'],
            'ends_at'           => ['sometimes', 'nullable', 'date'],
            'resolve_at'        => ['sometimes', 'nullable', 'date'],
        ];
    }

    public function toDTO(int $adminId): CreateEventDTO
    {
        return new CreateEventDTO(
            title:           $this->input('title'),
            description:     $this->input('description'),
            subCategoryUlid: $this->input('sub_category_ulid'),
            yesInitialPrice: number_format((float) $this->input('yes_initial_price'), 4, '.', ''),
            feeRate:         number_format((float) $this->input('fee_rate', 0.01), 4, '.', ''),
            startsAt:        $this->input('starts_at'),
            endsAt:          $this->input('ends_at'),
            resolveAt:       $this->input('resolve_at'),
            adminId:         $adminId,
        );
    }
}
