<?php

namespace App\Contracts\Containers;

interface FeedContainerInterface
{
    public function feed(?string $cursor, int $viewerId): array;

    public function create(array $input, int $userId): array;

    public function toggleLike(int $postId, int $userId): array;

    public function comment(array $input, int $postId, int $userId): array;
}
