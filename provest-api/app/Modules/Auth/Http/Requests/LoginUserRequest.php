<?php

declare(strict_types=1);

namespace App\Modules\Auth\Http\Requests;

use App\Modules\Auth\DTOs\UserLoginDTO;
use Illuminate\Foundation\Http\FormRequest;

class LoginUserRequest extends FormRequest
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

    public function toDTO(): UserLoginDTO
    {
        return new UserLoginDTO(
            email:      $this->input('email'),
            password:   $this->input('password'),
            deviceName: $this->input('device_name', 'user-api'),
        );
    }
}
