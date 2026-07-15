<?php

namespace App\Validators;

use App\Constants\AuthenticationConstant;
use Illuminate\Validation\Rules\Password;

class UserValidator
{
    /**
     * @return array<string, mixed>
     */
    public function registrationRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:'.AuthenticationConstant::NAME_MAX_LENGTH],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:'.AuthenticationConstant::EMAIL_MAX_LENGTH, 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(AuthenticationConstant::PASSWORD_MIN_LENGTH)],
            'password_confirmation' => ['required', 'string'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function loginRules(): array
    {
        return [
            'email' => ['required', 'string', 'lowercase', 'email'],
            'password' => ['required', 'string'],
            'remember' => ['nullable', 'boolean'],
        ];
    }
}
