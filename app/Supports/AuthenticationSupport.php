<?php

namespace App\Supports;

use Illuminate\Support\Str;

final class AuthenticationSupport
{
    public static function throttleKey(string $email, ?string $ipAddress): string
    {
        return Str::transliterate(
            EmailSupport::normalize($email).'|'.($ipAddress ?? 'unknown'),
        );
    }
}
