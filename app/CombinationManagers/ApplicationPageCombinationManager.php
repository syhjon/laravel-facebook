<?php

namespace App\CombinationManagers;

use App\Combinations\ApplicationPageCombination;
use App\Services\UserService;

class ApplicationPageCombinationManager
{
    public function __construct(
        private readonly ApplicationPageCombination $applicationPageCombination,
        private readonly UserService $userService,
    ) {}

    /** @return array<string, mixed> */
    public function buildPageData(string $pageName, ?int $userId = null): array
    {
        $userProfile = $userId ? $this->userService->profile($userId) : null;

        return $this->applicationPageCombination->page($pageName, $userProfile);
    }
}
