<?php

namespace App\Storage;

use App\Constants\UserConstant;
use App\Contracts\Storage\ProfileImageStorageInterface;
use Illuminate\Filesystem\FilesystemManager;
use Illuminate\Http\UploadedFile;
use RuntimeException;

class LocalProfileImageStorage implements ProfileImageStorageInterface
{
    public function __construct(
        private readonly FilesystemManager $filesystemManager,
    ) {}

    public function store(UploadedFile $profileImage, int $userId): string
    {
        $profileImageDirectory = sprintf(UserConstant::PROFILE_IMAGE_DIRECTORY_PATTERN, $userId);
        $storedProfileImagePath = $this->filesystemManager
            ->disk(UserConstant::PROFILE_IMAGE_DISK)
            ->putFile($profileImageDirectory, $profileImage, 'public');

        if (! is_string($storedProfileImagePath)) {
            throw new RuntimeException('個人照片儲存失敗。');
        }

        return $storedProfileImagePath;
    }

    public function delete(?string $profileImagePath): void
    {
        if (! $profileImagePath) {
            return;
        }

        $this->filesystemManager
            ->disk(UserConstant::PROFILE_IMAGE_DISK)
            ->delete($profileImagePath);
    }

    public function publicUrl(?string $profileImagePath): ?string
    {
        if (! $profileImagePath) {
            return null;
        }

        return UserConstant::PROFILE_IMAGE_PUBLIC_PATH_PREFIX.'/'.ltrim($profileImagePath, '/');
    }
}
