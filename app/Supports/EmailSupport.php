<?php

namespace App\Supports;

use Illuminate\Support\Str;

final class EmailSupport
{
    public static function normalize(mixed $email): string
    {
        return Str::lower(trim((string) $email));
    }
}
