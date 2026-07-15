<?php

namespace App\CombinationManagers;

use App\Combinations\AuthenticationPageCombination;
use App\Services\UserService;

class AuthenticationPageCombinationManager
{
    public function __construct(
        private readonly AuthenticationPageCombination $pageCombination,
        private readonly UserService $userService,
    ) {}

    /**
     * @return array{project: array{name: string, technology_label: string, theme: array<string, string>}, constraints: array<string, int>, page: string, user: array<string, mixed>|null, routes: array<string, string>}
     */
    public function build(string $page, ?int $userId = null): array
    {
        $user = $userId ? $this->userService->profile($userId) : null;

        return $this->pageCombination->page($page, $user);
    }
}
