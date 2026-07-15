<?php

namespace App\ServiceManagers;

use App\Contracts\ServiceManagers\AuthenticationServiceManagerInterface;
use App\Services\AuthenticationService;
use App\Services\UserService;

class AuthenticationServiceManager implements AuthenticationServiceManagerInterface
{
    public function __construct(
        private readonly AuthenticationService $authenticationService,
        private readonly UserService $userService,
    ) {}

    /**
     * @param  array{name: string, email: string, password: string}  $attributes
     */
    public function register(array $attributes): void
    {
        $user = $this->userService->create($attributes);
        $this->authenticationService->login($user);
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
