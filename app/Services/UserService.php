<?php

namespace App\Services;

use App\Combinations\UserCombination;
use App\Contracts\Repositories\UserRepositoryInterface;
use App\ExceptionCodes\UserExceptionCode;
use App\Exceptions\DomainNotFoundException;
use App\Models\User;

class UserService
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly UserCombination $userCombination,
    ) {}

    /**
     * @param  array{name: string, email: string, password: string}  $attributes
     */
    public function create(array $attributes): User
    {
        return $this->userRepository->create($attributes);
    }

    /**
     * @return array{id: int, name: string, email: string, member_since: string, initials: string}
     */
    public function profile(int $userId): array
    {
        $requestedUser = $this->userRepository->find($userId);

        if (! $requestedUser) {
            throw new DomainNotFoundException(
                UserExceptionCode::USER_NOT_FOUND,
                '找不到會員資料。',
            );
        }

        return $this->userCombination->profile($requestedUser);
    }
}
