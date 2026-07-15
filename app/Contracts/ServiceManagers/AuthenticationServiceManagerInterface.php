<?php

namespace App\Contracts\ServiceManagers;

interface AuthenticationServiceManagerInterface
{
    /**
     * @param  array{name: string, email: string, password: string}  $attributes
     */
    public function register(array $attributes): void;

    /**
     * @param  array{email: string, password: string, remember?: bool}  $credentials
     */
    public function authenticate(array $credentials, ?string $ipAddress): void;

    public function logout(): void;
}
