<?php

namespace App\Constants;

final class UserConstant
{
    public const CACHE_TTL_SECONDS = 300;

    public const CACHE_PAYLOAD_VERSION = 2;

    public const CACHE_KEY_PATTERN = '[UserById][user_id:%d]';

    public const CACHEABLE_ATTRIBUTES = [
        'id',
        'name',
        'email',
        'email_verified_at',
        'created_at',
        'updated_at',
    ];

    public const INITIALS_LENGTH = 1;
}
