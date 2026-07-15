<?php

namespace App\Contracts\Containers;

interface AuthenticationContainerInterface
{
    /**
     * @return array<string, mixed>
     */
    public function page(string $page, ?int $userId = null): array;

    /**
     * @param  array{name: string, email: string, password: string}  $attributes
     */
    public function register(array $attributes): void;

    /**
     * @param  array{email: string, password: string, remember: bool}  $credentials
     */
    public function login(array $credentials, ?string $ipAddress): void;

    public function logout(): void;
}
