<?php

namespace App\ServiceManagers;

use App\Combinations\PostCombination;
use App\Contracts\ServiceManagers\PostServiceManagerInterface;
use App\Services\PostService;

class PostServiceManager implements PostServiceManagerInterface
{
    public function __construct(
        private readonly PostService $postService,
        private readonly PostCombination $postCombination,
    ) {}

    public function feed(?string $cursor, int $viewerId): array
    {
        return $this->postCombination->feed(
            $this->postService->feed($cursor, $viewerId),
        );
    }

    /** @param array{body: string} $attributes */
    public function create(int $userId, array $attributes): array
    {
        return $this->postCombination->post(
            $this->postService->create($userId, $attributes),
        );
    }

    public function toggleLike(int $postId, int $userId): array
    {
        return $this->postCombination->post(
            $this->postService->toggleLike($postId, $userId),
        );
    }

    public function comment(int $postId, int $userId, string $body): array
    {
        return $this->postCombination->post(
            $this->postService->comment($postId, $userId, $body),
        );
    }
}
