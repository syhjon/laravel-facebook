<?php

namespace App\Containers\Authentication;

use App\CombinationManagers\AuthenticationPageCombinationManager;
use App\Contracts\Containers\AuthenticationContainerInterface;
use App\ServiceManagers\AuthenticationServiceManager;
use Illuminate\Support\Facades\DB;

class WebAuthenticationContainer implements AuthenticationContainerInterface
{
    public function __construct(
        private readonly AuthenticationServiceManager $authenticationServiceManager,
        private readonly AuthenticationPageCombinationManager $pageCombinationManager,
    ) {}

    public function page(string $page, ?int $userId = null): array
    {
        return $this->pageCombinationManager->build($page, $userId);
    }

    public function register(array $attributes): void
    {
        DB::transaction(
            fn () => $this->authenticationServiceManager->register($attributes),
        );
    }

    public function login(array $credentials, ?string $ipAddress): void
    {
        $this->authenticationServiceManager->authenticate($credentials, $ipAddress);
    }

    public function logout(): void
    {
        $this->authenticationServiceManager->logout();
    }
}
