<?php

namespace App\Containers\Authentication;

use App\CombinationManagers\ApplicationPageCombinationManager;
use App\Contracts\Containers\AuthenticationContainerInterface;
use App\Contracts\ServiceManagers\AuthenticationServiceManagerInterface;
use App\Contracts\Transactions\TransactionManagerInterface;

class WebAuthenticationContainer implements AuthenticationContainerInterface
{
    public function __construct(
        private readonly AuthenticationServiceManagerInterface $authenticationServiceManager,
        private readonly ApplicationPageCombinationManager $applicationPageCombinationManager,
        private readonly TransactionManagerInterface $transactionManager,
    ) {}

    public function page(string $pageName, ?int $userId = null): array
    {
        return $this->applicationPageCombinationManager->buildPageData($pageName, $userId);
    }

    public function register(array $attributes): void
    {
        $this->transactionManager->run(
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
