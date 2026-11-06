<?php

namespace App\Services;

use App\Combinations\UserCombination;
use App\Contracts\Repositories\UserRepositoryInterface;
use App\Contracts\Storage\ProfileImageStorageInterface;
use App\ExceptionCodes\UserExceptionCode;
use App\Exceptions\DomainNotFoundException;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Throwable;

class UserService
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly UserCombination $userCombination,
        private readonly ProfileImageStorageInterface $profileImageStorage,
    ) {}

    /**
     * @param  array{name: string, email: string, password: string}  $attributes
     */
    public function create(array $attributes): User
    {
        return $this->userRepository->create($attributes);
    }

    /**
     * @return array{id: int, name: string, email: string, member_since: string, initials: string, profile_image_url: string|null}
     */
    public function profile(int $userId): array
    {
        return $this->userCombination->profile($this->findUserOrFail($userId));
    }

    /**
     * @param  array{name: string, email: string}  $profileAttributes
     * @return array{id: int, name: string, email: string, member_since: string, initials: string, profile_image_url: string|null}
     */
    public function updateProfile(int $userId, array $profileAttributes): array
    {
        $updatedUser = $this->userRepository->updateProfile($userId, $profileAttributes);

        if (! $updatedUser) {
            $this->throwUserNotFoundException();
        }

        return $this->userCombination->profile($updatedUser);
    }

    /** @return array{id: int, name: string, email: string, member_since: string, initials: string, profile_image_url: string|null} */
    public function updateProfileImage(int $userId, UploadedFile $profileImage): array
    {
        $requestedUser = $this->findUserOrFail($userId);
        $previousProfileImagePath = $requestedUser->profile_image_path;
        $storedProfileImagePath = $this->profileImageStorage->store($profileImage, $userId);

        try {
            $updatedUser = $this->userRepository->updateProfileImagePath($userId, $storedProfileImagePath);

            if (! $updatedUser) {
                $this->throwUserNotFoundException();
            }
        } catch (Throwable $throwable) {
            $this->profileImageStorage->delete($storedProfileImagePath);

            throw $throwable;
        }

        $this->profileImageStorage->delete($previousProfileImagePath);

        return $this->userCombination->profile($updatedUser);
    }

    private function findUserOrFail(int $userId): User
    {
        $requestedUser = $this->userRepository->find($userId);

        if (! $requestedUser) {
            $this->throwUserNotFoundException();
        }

        return $requestedUser;
    }

    private function throwUserNotFoundException(): never
    {
        throw new DomainNotFoundException(
            UserExceptionCode::USER_NOT_FOUND,
            '找不到會員資料。',
        );
    }
}
