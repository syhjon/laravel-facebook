<?php

namespace App\ServiceManagers;

use App\Contracts\ServiceManagers\UserProfileServiceManagerInterface;
use App\Services\UserService;
use Illuminate\Http\UploadedFile;

class UserProfileServiceManager implements UserProfileServiceManagerInterface
{
    public function __construct(
        private readonly UserService $userService,
    ) {}

    public function profile(int $userId): array
    {
        return $this->userService->profile($userId);
    }

    public function updateProfile(int $userId, array $profileAttributes): array
    {
        return $this->userService->updateProfile($userId, $profileAttributes);
    }

    public function updateProfileImage(int $userId, UploadedFile $profileImage): array
    {
        return $this->userService->updateProfileImage($userId, $profileImage);
    }
}
