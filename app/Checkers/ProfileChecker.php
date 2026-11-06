<?php

namespace App\Checkers;

use App\Validators\UserValidator;

class ProfileChecker
{
    public function __construct(
        private readonly UserValidator $userValidator,
    ) {}

    /** @return array<string, mixed> */
    public function updateRules(int $userId): array
    {
        return $this->userValidator->profileUpdateRules($userId);
    }

    /** @return array<string, mixed> */
    public function imageUpdateRules(): array
    {
        return $this->userValidator->profileImageUpdateRules();
    }
}
