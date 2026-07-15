<?php

namespace App\ExceptionCodes;

final class AuthenticationExceptionCode
{
    public const REGISTRATION_DATA_INVALID = 1101001;

    public const LOGIN_DATA_INVALID = 1101002;

    public const CREDENTIALS_INVALID = 1101003;

    public const TOO_MANY_ATTEMPTS = 1101004;
}
