<?php

namespace App\Contracts\Repositories;

use App\Models\Comment;
use App\Models\Post;
use Illuminate\Pagination\CursorPaginator;

interface PostRepositoryInterface
{
    public function feed(?string $cursor, int $viewerId): CursorPaginator;

    /** @param array{body: string} $attributes */
    public function create(int $userId, array $attributes): Post;

    public function findForFeed(int $postId, int $viewerId): ?Post;

    public function toggleLike(int $postId, int $userId): bool;

    public function createComment(int $postId, int $userId, string $body): Comment;
}
