<?php

namespace App\Contracts\Storage;

use Illuminate\Http\UploadedFile;

interface ProfileImageStorageInterface
{
    public function store(UploadedFile $profileImage, int $userId): string;

    public function delete(?string $profileImagePath): void;

    public function publicUrl(?string $profileImagePath): ?string;
}
