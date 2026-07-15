<?php

namespace App\Repositories;

use App\Constants\PostConstant;
use App\Contracts\Repositories\PostRepositoryInterface;
use App\Models\Comment;
use App\Models\Post;
use App\Models\PostLike;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\Cursor;
use Illuminate\Pagination\CursorPaginator;

class PostRepository implements PostRepositoryInterface
{
    public function __construct(
        private readonly Post $postModel,
        private readonly Comment $commentModel,
        private readonly PostLike $postLikeModel,
    ) {}

    public function feed(?string $cursor, int $viewerId): CursorPaginator
    {
        return $this->feedQuery($viewerId)
            ->orderByDesc('id')
            ->cursorPaginate(
                PostConstant::FEED_PER_PAGE,
                ['*'],
                'cursor',
                $cursor ? Cursor::fromEncoded($cursor) : null,
            );
    }

    public function create(int $userId, array $attributes): Post
    {
        return $this->postModel->newQuery()->create([
            'user_id' => $userId,
            'body' => $attributes['body'],
        ]);
    }

    public function findForFeed(int $postId, int $viewerId): ?Post
    {
        return $this->feedQuery($viewerId)->find($postId);
    }

    public function toggleLike(int $postId, int $userId): bool
    {
        $query = $this->postLikeModel->newQuery()
            ->where('post_id', $postId)
            ->where('user_id', $userId);

        if ($query->exists()) {
            $query->delete();

            return false;
        }

        $this->postLikeModel->newQuery()->create([
            'post_id' => $postId,
            'user_id' => $userId,
        ]);

        return true;
    }

    public function createComment(int $postId, int $userId, string $body): Comment
    {
        return $this->commentModel->newQuery()->create([
            'post_id' => $postId,
            'user_id' => $userId,
            'body' => $body,
        ]);
    }

    private function feedQuery(int $viewerId): Builder
    {
        return $this->postModel->newQuery()
            ->with([
                'user:id,name',
                'comments' => fn ($query) => $query
                    ->with('user:id,name')
                    ->oldest('id'),
            ])
            ->withCount(['likes', 'comments'])
            ->withExists([
                'likes as liked_by_viewer' => fn ($query) => $query->where('user_id', $viewerId),
            ]);
    }
}
