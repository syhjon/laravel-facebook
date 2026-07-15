<?php

namespace App\Contracts\Containers;

interface FeedContainerInterface
{
    public function feed(?string $cursor, int $viewerId): array;

    /** @param array{body: string} $attributes */
    public function create(array $attributes, int $userId): array;

    public function toggleLike(int $postId, int $userId): array;

    public function comment(string $body, int $postId, int $userId): array;
}
