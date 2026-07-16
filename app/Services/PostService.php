<?php

namespace App\Services;

use App\Contracts\Repositories\PostRepositoryInterface;
use App\ExceptionCodes\PostExceptionCode;
use App\Exceptions\DomainNotFoundException;
use App\Models\Post;
use Illuminate\Pagination\CursorPaginator;

class PostService
{
    public function __construct(
        private readonly PostRepositoryInterface $postRepository,
    ) {}

    public function feed(?string $cursor, int $viewerId): CursorPaginator
    {
        return $this->postRepository->feed($cursor, $viewerId);
    }

    /** @param array{body: string} $attributes */
    public function create(int $userId, array $attributes): Post
    {
        $createdPost = $this->postRepository->create($userId, $attributes);

        return $this->findForFeed($createdPost->getKey(), $userId);
    }

    public function toggleLike(int $postId, int $userId): Post
    {
        $this->findForFeed($postId, $userId);
        $this->postRepository->toggleLike($postId, $userId);

        return $this->findForFeed($postId, $userId);
    }

    public function comment(int $postId, int $userId, string $body): Post
    {
        $this->findForFeed($postId, $userId);
        $this->postRepository->createComment($postId, $userId, $body);

        return $this->findForFeed($postId, $userId);
    }

    private function findForFeed(int $postId, int $viewerId): Post
    {
        $requestedPost = $this->postRepository->findForFeed($postId, $viewerId);

        if (! $requestedPost) {
            throw new DomainNotFoundException(
                PostExceptionCode::POST_NOT_FOUND,
                '找不到這篇貼文。',
            );
        }

        return $requestedPost;
    }
}
