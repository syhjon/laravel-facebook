<?php

namespace App\Http\Requests\Profile;

use App\Checkers\ProfileChecker;
use App\ExceptionCodes\UserExceptionCode;
use App\Http\Requests\ApiRequest;
use Illuminate\Http\UploadedFile;

class UpdateProfileImageRequest extends ApiRequest
{
    /** @return array<string, mixed> */
    public function rules(ProfileChecker $profileChecker): array
    {
        return $profileChecker->imageUpdateRules();
    }

    public function profileImage(): UploadedFile
    {
        /** @var UploadedFile $validatedProfileImage */
        $validatedProfileImage = $this->file('profile_image');

        return $validatedProfileImage;
    }

    protected function validationExceptionCode(): int
    {
        return UserExceptionCode::PROFILE_IMAGE_INVALID;
    }
}
