<?php

namespace App\Contracts\ServiceManagers;

interface PostServiceManagerInterface
{
    /**
     * @return array{data: array<int, array<string, mixed>>, meta: array<string, mixed>}
     */
    public function feed(?string $cursor, int $viewerId): array;

    /**
     * @param  array{body: string}  $attributes
     * @return array<string, mixed>
     */
    public function create(int $userId, array $attributes): array;

    /** @return array<string, mixed> */
    public function toggleLike(int $postId, int $userId): array;

    /** @return array<string, mixed> */
    public function comment(int $postId, int $userId, string $body): array;
}
