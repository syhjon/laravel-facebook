<?php

namespace App\Validators;

use App\Constants\AuthenticationConstant;
use App\Constants\UserConstant;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;
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

    /** @return array<string, mixed> */
    public function profileUpdateRules(int $userId): array
    {
        return [
            'name' => ['required', 'string', 'max:'.AuthenticationConstant::NAME_MAX_LENGTH],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:'.AuthenticationConstant::EMAIL_MAX_LENGTH,
                Rule::unique('users', 'email')->ignore($userId),
            ],
        ];
    }

    /** @return array<string, mixed> */
    public function profileImageUpdateRules(): array
    {
        return [
            'profile_image' => [
                'required',
                File::image()
                    ->types(['jpg', 'jpeg', 'png', 'webp'])
                    ->max(UserConstant::PROFILE_IMAGE_MAX_SIZE)
                    ->dimensions(
                        Rule::dimensions()
                            ->maxWidth(UserConstant::PROFILE_IMAGE_MAX_WIDTH)
                            ->maxHeight(UserConstant::PROFILE_IMAGE_MAX_HEIGHT),
                    ),
            ],
        ];
    }
}
