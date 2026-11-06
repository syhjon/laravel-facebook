<?php

namespace App\ServiceManagers;

use App\CombinationManagers\ApplicationPageCombinationManager;
use App\Contracts\Transactions\TransactionManagerInterface;
use App\Services\AuthenticationService;
use App\Services\UserService;

class AuthenticationServiceManager
{
    public function __construct(
        private readonly AuthenticationService $authenticationService,
        private readonly UserService $userService,
        private readonly ApplicationPageCombinationManager $applicationPageCombinationManager,
        private readonly TransactionManagerInterface $transactionManager,
    ) {}

    public function page(string $pageName, ?int $userId = null): array
    {
        return $this->applicationPageCombinationManager->buildPageData($pageName, $userId);
    }

    /**
     * @param  array{name: string, email: string, password: string}  $attributes
     */
    public function register(array $attributes): void
    {
        $this->transactionManager->run(function () use ($attributes): void {
            $registeredUser = $this->userService->create($attributes);
            $this->authenticationService->login($registeredUser);
        });
    }

    /**
     * @param  array{email: string, password: string, remember?: bool}  $credentials
     */
    public function authenticate(array $credentials, ?string $ipAddress): void
    {
        $this->authenticationService->authenticate($credentials, $ipAddress);
    }

    public function logout(): void
    {
        $this->authenticationService->logout();
    }
}
