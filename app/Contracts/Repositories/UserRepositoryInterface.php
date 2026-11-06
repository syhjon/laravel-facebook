<?php

namespace App\Contracts\Repositories;

use App\Models\User;

interface UserRepositoryInterface
{
    /**
     * @param  array{name: string, email: string, password: string}  $attributes
     */
    public function create(array $attributes): User;

    public function find(int $userId): ?User;

    /** @param array{name: string, email: string} $profileAttributes */
    public function updateProfile(int $userId, array $profileAttributes): ?User;

    public function updateProfileImagePath(int $userId, string $profileImagePath): ?User;
}
