<?php

namespace App\Repositories;

use App\CacheManagers\UserCacheManager;
use App\Contracts\Repositories\UserRepositoryInterface;
use App\Models\User;

class UserRepository implements UserRepositoryInterface
{
    public function __construct(
        private readonly User $model,
        private readonly UserCacheManager $cacheManager,
    ) {}

    public function create(array $attributes): User
    {
        $user = $this->model->newQuery()->create($attributes);
        $this->cacheManager->forget($user->getKey());

        return $user;
    }

    public function find(int $userId): ?User
    {
        return $this->cacheManager->remember(
            $userId,
            fn (): ?User => $this->model->newQuery()->find($userId),
        );
    }
}
