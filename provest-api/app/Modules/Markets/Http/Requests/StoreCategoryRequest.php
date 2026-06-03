<?php

declare(strict_types=1);

namespace App\Modules\Markets\Http\Requests;

use App\Modules\Markets\DTOs\CreateCategoryDTO;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class StoreCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'      => ['required', 'string', 'max:255'],
            'slug'      => ['sometimes', 'string', 'max:255', 'regex:/^[a-z0-9\-]+$/'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function toDTO(): CreateCategoryDTO
    {
        return new CreateCategoryDTO(
            name:     $this->input('name'),
            slug:     $this->input('slug', Str::slug($this->input('name'))),
            isActive: (bool) $this->input('is_active', true),
        );
    }
}
