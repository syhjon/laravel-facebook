<?php

namespace App\Repositories;

use App\CacheManagers\UserCacheManager;
use App\Contracts\Repositories\UserRepositoryInterface;
use App\Models\User;

class UserRepository implements UserRepositoryInterface
{
    public function __construct(
        private readonly User $userModel,
        private readonly UserCacheManager $userCacheManager,
    ) {}

    public function create(array $attributes): User
    {
        $createdUser = $this->userModel->newQuery()->create($attributes);
        $this->userCacheManager->forgetUser($createdUser->getKey());

        return $createdUser;
    }

    public function find(int $userId): ?User
    {
        return $this->userCacheManager->rememberUser(
            $userId,
            fn (): ?User => $this->userModel->newQuery()->find($userId),
        );
    }

    public function updateProfile(int $userId, array $profileAttributes): ?User
    {
        $userToUpdate = $this->userModel->newQuery()->find($userId);

        if (! $userToUpdate) {
            return null;
        }

        $userToUpdate->fill($profileAttributes);
        $userToUpdate->save();
        $this->userCacheManager->forgetUser($userId);

        return $userToUpdate->refresh();
    }

    public function updateProfileImagePath(int $userId, string $profileImagePath): ?User
    {
        $userToUpdate = $this->userModel->newQuery()->find($userId);

        if (! $userToUpdate) {
            return null;
        }

        $userToUpdate->profile_image_path = $profileImagePath;
        $userToUpdate->save();
        $this->userCacheManager->forgetUser($userId);

        return $userToUpdate->refresh();
    }
}
