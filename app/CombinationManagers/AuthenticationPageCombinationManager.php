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
     * @return array<string, mixed>
     */
    public function build(string $page, ?int $userId = null): array
    {
        $userProfile = $userId ? $this->userService->profile($userId) : null;

        return $this->pageCombination->page($page, $userProfile);
    }
}
