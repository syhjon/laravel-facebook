<?php

namespace App\Contracts\Containers;

interface AuthenticationContainerInterface
{
    /**
     * @return array<string, mixed>
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
