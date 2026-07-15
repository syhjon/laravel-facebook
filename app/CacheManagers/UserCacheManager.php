<?php

namespace App\CacheManagers;

use App\Constants\UserConstant;
use App\Models\User;
use Closure;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;

class UserCacheManager
{
    public function key(int $userId): string
    {
        return sprintf(UserConstant::CACHE_KEY_PATTERN, $userId);
    }

    /**
     * @param  Closure(): ?User  $resolver
     */
    public function remember(int $userId, Closure $resolver): ?User
    {
        $key = $this->key($userId);
        $cached = Cache::get($key);

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
                'version' => UserConstant::CACHE_PAYLOAD_VERSION,
                'attributes' => Arr::only(
                    $user->getAttributes(),
                    UserConstant::CACHEABLE_ATTRIBUTES,
                ),
            ],
            UserConstant::CACHE_TTL_SECONDS,
        );
    }

    private function isValidPayload(mixed $cached): bool
    {
        if (! is_array($cached)
            || ($cached['version'] ?? null) !== UserConstant::CACHE_PAYLOAD_VERSION
            || ! isset($cached['attributes'])
            || ! is_array($cached['attributes'])) {
            return false;
        }

        foreach ($cached['attributes'] as $attribute) {
            if (! is_scalar($attribute) && $attribute !== null) {
                return false;
            }
        }

        return true;
    }
}
