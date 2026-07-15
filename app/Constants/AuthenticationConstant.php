<?php

namespace App\Constants;

final class AuthenticationConstant
{
    public const PAGE_LOGIN = 'login';

    public const PAGE_REGISTER = 'register';

    public const PAGE_DASHBOARD = 'dashboard';

    public const ROUTE_LOGIN = 'login';

    public const ROUTE_REGISTER = 'register';

    public const ROUTE_DASHBOARD = 'dashboard';

    public const ROUTE_LOGOUT = 'logout';

    public const URI_LOGIN = '/login';

    public const URI_REGISTER = '/register';

    public const URI_DASHBOARD = '/dashboard';

    public const URI_LOGOUT = '/logout';

    public const MAX_LOGIN_ATTEMPTS = 5;

    public const LOGIN_DECAY_SECONDS = 60;

    public const NAME_MAX_LENGTH = 255;

    public const EMAIL_MAX_LENGTH = 255;

    public const PASSWORD_MIN_LENGTH = 8;
}
