<?php

namespace App\CacheManagers;

use App\Constants\UserConstant;
use App\Models\User;
use Closure;
use Illuminate\Support\Facades\Cache;

class UserCacheManager
{
    private const CACHE_PAYLOAD_VERSION = 1;

    private const CACHEABLE_ATTRIBUTES = [
        'id',
        'name',
        'email',
        'email_verified_at',
        'created_at',
        'updated_at',
    ];

    public function key(int $userId): string
    {
        return "[UserById][user_id:{$userId}]";
    }

    /**
     * @param  Closure(): ?User  $resolver
     */
    public function remember(int $userId, Closure $resolver): ?User
    {
        $key = $this->key($userId);
        $cached = Cache::get($key);

        if ($cached instanceof User) {
            $this->store($key, $cached);

            return $cached;
        }

        if ($this->isValidPayload($cached)) {
            return (new User)->newFromBuilder($cached['attributes']);
        }

        if ($cached !== null) {
            Cache::forget($key);
        }

        $user = $resolver();

        if ($user) {
            $this->store($key, $user);
        }

        return $user;
    }

    public function forget(int $userId): void
    {
        Cache::forget($this->key($userId));
    }

    private function store(string $key, User $user): void
    {
        Cache::put(
            $key,
            [
                'version' => self::CACHE_PAYLOAD_VERSION,
                'attributes' => $user->only(self::CACHEABLE_ATTRIBUTES),
            ],
            UserConstant::CACHE_TTL_SECONDS,
        );
    }

    private function isValidPayload(mixed $cached): bool
    {
        return is_array($cached)
            && ($cached['version'] ?? null) === self::CACHE_PAYLOAD_VERSION
            && isset($cached['attributes'])
            && is_array($cached['attributes']);
    }
}
