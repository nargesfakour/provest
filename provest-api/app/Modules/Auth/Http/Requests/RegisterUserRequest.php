<?php

declare(strict_types=1);

namespace App\Modules\Auth\Http\Requests;

use App\Modules\Auth\DTOs\UserRegisterDTO;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'        => ['required', 'string', 'max:255'],
            'email'       => ['required', 'email', 'unique:users,email'],
            'password'    => [
                'required',
                'string',
                'confirmed',
                Password::min(8)->mixedCase()->numbers()->symbols(),
            ],
            'device_name' => ['sometimes', 'string', 'max:255'],
        ];
    }

    public function toDTO(): UserRegisterDTO
    {
        return new UserRegisterDTO(
            name:       $this->input('name'),
            email:      $this->input('email'),
            password:   $this->input('password'),
            deviceName: $this->input('device_name', 'user-api'),
        );
    }
}
