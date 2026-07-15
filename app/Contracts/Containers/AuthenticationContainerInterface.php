<?php

namespace App\Contracts\Containers;

interface AuthenticationContainerInterface
{
    /**
     * @return array{page: string, user: array<string, mixed>|null, routes: array<string, string>}
     */
    public function page(string $page, ?int $userId = null): array;

    /**
     * @param  array<string, mixed>  $input
     */
    public function register(array $input): void;

    /**
     * @param  array<string, mixed>  $input
     */
    public function login(array $input, ?string $ipAddress): void;

    public function logout(): void;
}
