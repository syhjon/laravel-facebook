<?php

namespace App\Http\Requests\Authentication;

use App\Checkers\AuthenticationChecker;
use App\ExceptionCodes\AuthenticationExceptionCode;
use App\Http\Requests\ApiRequest;
use App\Supports\EmailSupport;

class RegisterRequest extends ApiRequest
{
    /** @return array<string, mixed> */
    public function rules(AuthenticationChecker $authenticationChecker): array
    {
        return $authenticationChecker->registrationRules();
    }

    /** @return array{name: string, email: string, password: string} */
    public function payload(): array
    {
        /** @var array{name: string, email: string, password: string} $validatedRegistrationData */
        $validatedRegistrationData = $this->safe()->only(['name', 'email', 'password']);

        return $validatedRegistrationData;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => EmailSupport::normalize($this->input('email')),
        ]);
    }

    protected function validationExceptionCode(): int
    {
        return AuthenticationExceptionCode::REGISTRATION_DATA_INVALID;
    }
}
