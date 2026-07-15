<?php

namespace App\Containers\Feed;

use App\Checkers\PostChecker;
use App\Contracts\Containers\FeedContainerInterface;
use App\ServiceManagers\PostServiceManager;
use Illuminate\Support\Facades\DB;

class WebFeedContainer implements FeedContainerInterface
{
    public function __construct(
        private readonly PostChecker $postChecker,
        private readonly PostServiceManager $postServiceManager,
    ) {}

    public function feed(?string $cursor, int $viewerId): array
    {
        return $this->postServiceManager->feed($cursor, $viewerId);
    }

    public function create(array $input, int $userId): array
    {
        return DB::transaction(
            fn (): array => $this->postServiceManager->create(
                $userId,
                $this->postChecker->checkPost($input),
            ),
        );
    }

    public function toggleLike(int $postId, int $userId): array
    {
        return DB::transaction(
            fn (): array => $this->postServiceManager->toggleLike($postId, $userId),
        );
    }

    public function comment(array $input, int $postId, int $userId): array
    {
        return DB::transaction(
            function () use ($input, $postId, $userId): array {
                $validated = $this->postChecker->checkComment($input);

                return $this->postServiceManager->comment(
                    $postId,
                    $userId,
                    $validated['body'],
                );
            },
        );
    }
}
