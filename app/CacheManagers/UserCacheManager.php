<?php

namespace App\CacheManagers;

use App\Constants\UserConstant;
use App\Models\User;
use Closure;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;

class UserCacheManager
{
    public function cacheKeyForUser(int $userId): string
    {
        return sprintf(UserConstant::CACHE_KEY_PATTERN, $userId);
    }

    /**
     * @param  Closure(): ?User  $userResolver
     */
    public function rememberUser(int $userId, Closure $userResolver): ?User
    {
        $userCacheKey = $this->cacheKeyForUser($userId);
        $cachedUserPayload = Cache::get($userCacheKey);

        if ($this->isValidUserCachePayload($cachedUserPayload)) {
            return (new User)->newFromBuilder($cachedUserPayload['attributes']);
        }

        if ($cachedUserPayload !== null) {
            Cache::forget($userCacheKey);
        }

        $resolvedUser = $userResolver();

        if ($resolvedUser) {
            $this->storeUserCachePayload($userCacheKey, $resolvedUser);
        }

        return $resolvedUser;
    }

    public function forgetUser(int $userId): void
    {
        Cache::forget($this->cacheKeyForUser($userId));
    }

    private function storeUserCachePayload(string $userCacheKey, User $user): void
    {
        Cache::put(
            $userCacheKey,
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

    private function isValidUserCachePayload(mixed $cachedUserPayload): bool
    {
        if (! is_array($cachedUserPayload)
            || ($cachedUserPayload['version'] ?? null) !== UserConstant::CACHE_PAYLOAD_VERSION
            || ! isset($cachedUserPayload['attributes'])
            || ! is_array($cachedUserPayload['attributes'])) {
            return false;
        }

        foreach ($cachedUserPayload['attributes'] as $cachedUserAttribute) {
            if (! is_scalar($cachedUserAttribute) && $cachedUserAttribute !== null) {
                return false;
            }
        }

        return true;
    }
}
