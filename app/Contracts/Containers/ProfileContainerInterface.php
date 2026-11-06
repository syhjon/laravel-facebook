<?php

namespace App\Contracts\Containers;

use Illuminate\Http\UploadedFile;

interface ProfileContainerInterface
{
    /** @return array<string, mixed> */
    public function page(int $userId): array;

    /** @return array{id: int, name: string, email: string, member_since: string, initials: string, profile_image_url: string|null} */
    public function profile(int $userId): array;

    /**
     * @param  array{name: string, email: string}  $profileAttributes
     * @return array{id: int, name: string, email: string, member_since: string, initials: string, profile_image_url: string|null}
     */
    public function updateProfile(int $userId, array $profileAttributes): array;

    /** @return array{id: int, name: string, email: string, member_since: string, initials: string, profile_image_url: string|null} */
    public function updateProfileImage(int $userId, UploadedFile $profileImage): array;
}
