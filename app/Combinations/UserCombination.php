<?php

namespace App\Combinations;

use App\Models\User;
use App\Supports\UserSupport;

class UserCombination
{
    /**
     * @return array{id: int, name: string, email: string, member_since: string, initials: string}
     */
    public function profile(User $user): array
    {
        return [
            ...$user->present(),
            'initials' => UserSupport::initials($user->name),
        ];
    }
}
