<?php

namespace App\ServiceManagers;

use App\CombinationManagers\ApplicationPageCombinationManager;
use App\Constants\ProfileConstant;
use App\Contracts\Transactions\TransactionManagerInterface;
use App\Services\UserService;
use Illuminate\Http\UploadedFile;

class UserProfileServiceManager
{
    public function __construct(
        private readonly UserService $userService,
        private readonly ApplicationPageCombinationManager $applicationPageCombinationManager,
        private readonly TransactionManagerInterface $transactionManager,
    ) {}

    public function page(int $userId): array
    {
        return $this->applicationPageCombinationManager->buildPageData(ProfileConstant::PAGE_PROFILE, $userId);
    }

    public function profile(int $userId): array
    {
        return $this->userService->profile($userId);
    }

    public function updateProfile(int $userId, array $profileAttributes): array
    {
        return $this->transactionManager->run(
            fn (): array => $this->userService->updateProfile($userId, $profileAttributes),
        );
    }

    public function updateProfileImage(int $userId, UploadedFile $profileImage): array
    {
        return $this->transactionManager->run(
            fn (): array => $this->userService->updateProfileImage($userId, $profileImage),
        );
    }
}
