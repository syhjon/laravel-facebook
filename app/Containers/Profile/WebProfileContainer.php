<?php

namespace App\Containers\Profile;

use App\CombinationManagers\ApplicationPageCombinationManager;
use App\Constants\ProfileConstant;
use App\Contracts\Containers\ProfileContainerInterface;
use App\Contracts\ServiceManagers\UserProfileServiceManagerInterface;
use App\Contracts\Transactions\TransactionManagerInterface;
use Illuminate\Http\UploadedFile;

class WebProfileContainer implements ProfileContainerInterface
{
    public function __construct(
        private readonly UserProfileServiceManagerInterface $userProfileServiceManager,
        private readonly ApplicationPageCombinationManager $applicationPageCombinationManager,
        private readonly TransactionManagerInterface $transactionManager,
    ) {}

    public function page(int $userId): array
    {
        return $this->applicationPageCombinationManager->buildPageData(ProfileConstant::PAGE_PROFILE, $userId);
    }

    public function profile(int $userId): array
    {
        return $this->userProfileServiceManager->profile($userId);
    }

    public function updateProfile(int $userId, array $profileAttributes): array
    {
        return $this->transactionManager->run(
            fn (): array => $this->userProfileServiceManager->updateProfile($userId, $profileAttributes),
        );
    }

    public function updateProfileImage(int $userId, UploadedFile $profileImage): array
    {
        return $this->transactionManager->run(
            fn (): array => $this->userProfileServiceManager->updateProfileImage($userId, $profileImage),
        );
    }
}
