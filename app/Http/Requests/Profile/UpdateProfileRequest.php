<?php

namespace App\Http\Requests\Profile;

use App\Checkers\ProfileChecker;
use App\ExceptionCodes\UserExceptionCode;
use App\Http\Requests\ApiRequest;
use App\Supports\EmailSupport;

class UpdateProfileRequest extends ApiRequest
{
    /** @return array<string, mixed> */
    public function rules(ProfileChecker $profileChecker): array
    {
        return $profileChecker->updateRules($this->userId());
    }

    /** @return array{name: string, email: string} */
    public function payload(): array
    {
        /** @var array{name: string, email: string} $validatedProfileData */
        $validatedProfileData = $this->safe()->only(['name', 'email']);

        return $validatedProfileData;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => EmailSupport::normalize($this->input('email')),
        ]);
    }

    protected function validationExceptionCode(): int
    {
        return UserExceptionCode::PROFILE_DATA_INVALID;
    }
}
