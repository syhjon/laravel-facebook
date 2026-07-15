<?php

namespace App\Containers\Feed;

use App\Contracts\Containers\FeedContainerInterface;
use App\Contracts\ServiceManagers\PostServiceManagerInterface;
use App\Contracts\Transactions\TransactionManagerInterface;

class WebFeedContainer implements FeedContainerInterface
{
    public function __construct(
        private readonly PostServiceManagerInterface $postServiceManager,
        private readonly TransactionManagerInterface $transactionManager,
    ) {}

    public function feed(?string $cursor, int $viewerId): array
    {
        return $this->postServiceManager->feed($cursor, $viewerId);
    }

    public function create(array $attributes, int $userId): array
    {
        return $this->transactionManager->run(
            fn (): array => $this->postServiceManager->create(
                $userId,
                $attributes,
            ),
        );
    }

    public function toggleLike(int $postId, int $userId): array
    {
        return $this->transactionManager->run(
            fn (): array => $this->postServiceManager->toggleLike($postId, $userId),
        );
    }

    public function comment(string $body, int $postId, int $userId): array
    {
        return $this->transactionManager->run(
            fn (): array => $this->postServiceManager->comment(
                $postId,
                $userId,
                $body,
            ),
        );
    }
}
