<?php

namespace App\Checkers;

use App\Validators\UserValidator;

class AuthenticationChecker
{
    public function __construct(
        private readonly UserValidator $userValidator,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function registrationRules(): array
    {
        return $this->userValidator->registrationRules();
    }

    /**
     * @return array<string, mixed>
     */
    public function loginRules(): array
    {
        return $this->userValidator->loginRules();
    }
}
