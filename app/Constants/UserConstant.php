<?php

namespace App\Constants;

final class UserConstant
{
    public const CACHE_TTL_SECONDS = 300;

    public const CACHE_PAYLOAD_VERSION = 3;

    public const CACHE_KEY_PATTERN = '[UserById][user_id:%d]';

    public const CACHEABLE_ATTRIBUTES = [
        'id',
        'name',
        'email',
        'profile_image_path',
        'email_verified_at',
        'created_at',
        'updated_at',
    ];

    public const INITIALS_LENGTH = 1;

    public const PROFILE_IMAGE_DISK = 'public';

    public const PROFILE_IMAGE_DIRECTORY_PATTERN = 'profile-images/%d';

    public const PROFILE_IMAGE_PUBLIC_PATH_PREFIX = '/storage';

    public const PROFILE_IMAGE_MAX_SIZE = '5mb';

    public const PROFILE_IMAGE_MAX_WIDTH = 2048;

    public const PROFILE_IMAGE_MAX_HEIGHT = 2048;
}
