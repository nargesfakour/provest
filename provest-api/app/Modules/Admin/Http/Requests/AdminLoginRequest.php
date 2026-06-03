<?php

declare(strict_types=1);

namespace App\Modules\Admin\Http\Requests;

use App\Modules\Admin\DTOs\AdminLoginDTO;
use Illuminate\Foundation\Http\FormRequest;

class AdminLoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email'       => ['required', 'email'],
            'password'    => ['required', 'string'],
            'device_name' => ['sometimes', 'string', 'max:255'],
        ];
    }

    public function toDTO(): AdminLoginDTO
    {
        return new AdminLoginDTO(
            email:      $this->input('email'),
            password:   $this->input('password'),
            deviceName: $this->input('device_name', 'admin-api'),
        );
    }
}
