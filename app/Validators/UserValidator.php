<?php

namespace App\Validators;

use App\ExceptionCodes\AuthenticationExceptionCode;
use App\Exceptions\DomainValidationException;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class UserValidator
{
    /**
     * @param  array<string, mixed>  $input
     * @return array{name: string, email: string, password: string}
     */
    public function validateRegistration(array $input): array
    {
        $validator = Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        if ($validator->fails()) {
            throw new DomainValidationException(
                $validator->errors()->toArray(),
                AuthenticationExceptionCode::REGISTRATION_DATA_INVALID,
            );
        }

        /** @var array{name: string, email: string, password: string} $validated */
        $validated = $validator->validated();

        return $validated;
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array{email: string, password: string, remember?: bool}
     */
    public function validateLogin(array $input): array
    {
        $validator = Validator::make($input, [
            'email' => ['required', 'string', 'lowercase', 'email'],
            'password' => ['required', 'string'],
            'remember' => ['nullable', 'boolean'],
        ]);

        if ($validator->fails()) {
            throw new DomainValidationException(
                $validator->errors()->toArray(),
                AuthenticationExceptionCode::LOGIN_DATA_INVALID,
            );
        }

        /** @var array{email: string, password: string, remember?: bool} $validated */
        $validated = $validator->validated();

        return $validated;
    }
}
