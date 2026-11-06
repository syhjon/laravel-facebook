<?php

namespace App\Combinations;

use App\Contracts\Storage\ProfileImageStorageInterface;
use App\Models\User;
use App\Supports\UserSupport;

class UserCombination
{
    public function __construct(
        private readonly ProfileImageStorageInterface $profileImageStorage,
    ) {}

    /**
     * @return array{id: int, name: string, email: string, member_since: string, initials: string, profile_image_url: string|null}
     */
    public function profile(User $user): array
    {
        return [
            ...$user->present(),
            'initials' => UserSupport::initials($user->name),
            'profile_image_url' => $this->profileImageStorage->publicUrl($user->profile_image_path),
        ];
    }
}
