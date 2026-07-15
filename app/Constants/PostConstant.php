<?php

namespace App\Constants;

final class PostConstant
{
    public const FEED_PER_PAGE = 10;

    public const BODY_MAX_LENGTH = 2000;

    public const COMMENT_MAX_LENGTH = 500;

    public const ROUTE_FEED = 'feed.index';

    public const ROUTE_POST_CREATE = 'posts.store';

    public const ROUTE_POST_LIKE = 'posts.likes.toggle';

    public const ROUTE_POST_COMMENT = 'posts.comments.store';

    public const URI_FEED = '/feed';

    public const URI_POSTS = '/posts';

    public const URI_POST_LIKE = '/posts/{postId}/likes';

    public const URI_POST_COMMENT = '/posts/{postId}/comments';
}
