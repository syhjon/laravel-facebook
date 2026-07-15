<?php

namespace App\CacheManagers;

use App\Constants\UserConstant;
use App\Models\User;
use Closure;
use Illuminate\Support\Facades\Cache;

class UserCacheManager
{
    public function key(int $userId): string
    {
        return "[UserById][user_id:{$userId}]";
    }

    /**
     * @param  Closure(): ?User  $resolver
     */
    public function remember(int $userId, Closure $resolver): ?User
    {
        return Cache::remember(
            $this->key($userId),
            UserConstant::CACHE_TTL_SECONDS,
            $resolver,
        );
    }

    public function forget(int $userId): void
    {
        Cache::forget($this->key($userId));
    }
}
