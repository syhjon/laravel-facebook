<?php

namespace App\Combinations;

use App\Models\Comment;
use App\Models\Post;
use App\Supports\UserSupport;
use Illuminate\Pagination\CursorPaginator;

class PostCombination
{
    /** @return array{data: array<int, array<string, mixed>>, meta: array{next_cursor: string|null, has_more: bool}} */
    public function feed(CursorPaginator $paginator): array
    {
        return [
            'data' => collect($paginator->items())
                ->map(fn (Post $post): array => $this->post($post))
                ->values()
                ->all(),
            'meta' => [
                'next_cursor' => $paginator->nextCursor()?->encode(),
                'has_more' => $paginator->hasMorePages(),
            ],
        ];
    }

    /** @return array<string, mixed> */
    public function post(Post $post): array
    {
        return [
            'id' => $post->getKey(),
            'body' => $post->body,
            'created_at' => $post->created_at?->format('Y/m/d H:i') ?? '',
            'author' => [
                'id' => $post->user->getKey(),
                'name' => $post->user->name,
                'initials' => UserSupport::initials($post->user->name),
            ],
            'liked' => (bool) $post->liked_by_viewer,
            'likes_count' => (int) $post->likes_count,
            'comments_count' => (int) $post->comments_count,
            'comments' => $post->comments
                ->map(fn (Comment $comment): array => $this->comment($comment))
                ->values()
                ->all(),
        ];
    }

    /** @return array<string, mixed> */
    private function comment(Comment $comment): array
    {
        return [
            'id' => $comment->getKey(),
            'body' => $comment->body,
            'created_at' => $comment->created_at?->format('Y/m/d H:i') ?? '',
            'author' => [
                'id' => $comment->user->getKey(),
                'name' => $comment->user->name,
                'initials' => UserSupport::initials($comment->user->name),
            ],
        ];
    }
}
