<?php

namespace App\Checkers;

use App\Supports\EmailSupport;
use App\Validators\UserValidator;

class AuthenticationChecker
{
    public function __construct(
        private readonly UserValidator $userValidator,
    ) {}

    /**
     * @param  array<string, mixed>  $input
     * @return array{name: string, email: string, password: string}
     */
    public function checkRegistration(array $input): array
    {
        $input['email'] = EmailSupport::normalize($input['email'] ?? null);

        return $this->userValidator->validateRegistration($input);
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array{email: string, password: string, remember?: bool}
     */
    public function checkLogin(array $input): array
    {
        $input['email'] = EmailSupport::normalize($input['email'] ?? null);

        return $this->userValidator->validateLogin($input);
    }
}
