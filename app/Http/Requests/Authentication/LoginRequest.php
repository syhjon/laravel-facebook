<?php

namespace App\Http\Requests\Authentication;

use App\Checkers\AuthenticationChecker;
use App\ExceptionCodes\AuthenticationExceptionCode;
use App\Http\Requests\ApiRequest;
use App\Supports\EmailSupport;

class LoginRequest extends ApiRequest
{
    /** @return array<string, mixed> */
    public function rules(AuthenticationChecker $authenticationChecker): array
    {
        return $authenticationChecker->loginRules();
    }

    /** @return array{email: string, password: string, remember: bool} */
    public function payload(): array
    {
        /** @var array{email: string, password: string, remember?: bool} $validated */
        $validated = $this->safe()->only(['email', 'password', 'remember']);

        return [
            'email' => $validated['email'],
            'password' => $validated['password'],
            'remember' => (bool) ($validated['remember'] ?? false),
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => EmailSupport::normalize($this->input('email')),
        ]);
    }

    protected function validationExceptionCode(): int
    {
        return AuthenticationExceptionCode::LOGIN_DATA_INVALID;
    }
}
