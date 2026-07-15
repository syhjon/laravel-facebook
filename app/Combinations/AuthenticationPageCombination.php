<?php

namespace App\Combinations;

class AuthenticationPageCombination
{
    /**
     * @param  array<string, mixed>|null  $user
     * @return array{page: string, user: array<string, mixed>|null, routes: array<string, string>}
     */
    public function page(string $page, ?array $user): array
    {
        return [
            'page' => $page,
            'user' => $user,
            'routes' => [
                'login' => route('login'),
                'register' => route('register'),
                'dashboard' => route('dashboard'),
                'logout' => route('logout'),
            ],
        ];
    }
}
