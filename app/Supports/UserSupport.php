<?php

namespace App\Supports;

use Illuminate\Support\Str;

final class UserSupport
{
    public static function initials(string $name): string
    {
        return Str::upper(Str::substr(trim($name), 0, 1));
    }
}
