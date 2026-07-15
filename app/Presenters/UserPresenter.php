<?php

namespace App\Presenters;

use App\Models\User;

class UserPresenter
{
    /**
     * @return array{id: int, name: string, email: string, member_since: string}
     */
    public static function present(User $user): array
    {
        return [
            'id' => $user->getKey(),
            'name' => $user->name,
            'email' => $user->email,
            'member_since' => $user->created_at?->toDateString() ?? '',
        ];
    }
}
