<?php

namespace App\Services;

use App\Constants\AuthenticationConstant;
use App\ExceptionCodes\AuthenticationExceptionCode;
use App\Exceptions\DomainValidationException;
use App\Supports\AuthenticationSupport;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;

class AuthenticationService
{
    public function login(Authenticatable $user): void
    {
        Auth::login($user);
    }

    /**
     * @param  array{email: string, password: string, remember?: bool}  $credentials
     */
    public function authenticate(array $credentials, ?string $ipAddress): void
    {
        $key = AuthenticationSupport::throttleKey($credentials['email'], $ipAddress);

        if (RateLimiter::tooManyAttempts($key, AuthenticationConstant::MAX_LOGIN_ATTEMPTS)) {
            $seconds = RateLimiter::availableIn($key);

            throw new DomainValidationException(
                ['email' => ["登入嘗試次數過多，請在 {$seconds} 秒後再試。"]],
                AuthenticationExceptionCode::TOO_MANY_ATTEMPTS,
            );
        }

        $authenticated = Auth::attempt(
            [
                'email' => $credentials['email'],
                'password' => $credentials['password'],
            ],
            (bool) ($credentials['remember'] ?? false),
        );

        if (! $authenticated) {
            RateLimiter::hit($key, AuthenticationConstant::LOGIN_DECAY_SECONDS);

            throw new DomainValidationException(
                ['email' => ['Email 或密碼不正確。']],
                AuthenticationExceptionCode::CREDENTIALS_INVALID,
            );
        }

        RateLimiter::clear($key);
    }

    public function logout(): void
    {
        Auth::logout();
    }
}
