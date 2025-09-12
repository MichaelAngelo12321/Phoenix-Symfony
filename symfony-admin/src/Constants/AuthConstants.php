<?php

declare(strict_types=1);

namespace App\Constants;

final readonly class AuthConstants
{
    public const SESSION_ADMIN_TOKEN = 'admin_token';
    public const SESSION_ADMIN_DATA = 'admin_data';

    public const ROUTE_LOGIN = 'app_login';
    public const ROUTE_LOGOUT = 'app_logout';
    public const ROUTE_CHECK_AUTH = 'app_check_auth';
    public const ROUTE_ADMIN_USERS_INDEX = 'admin_users_index';

    public const FLASH_SUCCESS = 'success';
    public const FLASH_ERROR = 'error';

    public const MESSAGE_LOGIN_SUCCESS = 'Pomyślnie zalogowano!';
    public const MESSAGE_NO_TOKEN = 'No token found';
    public const MESSAGE_INVALID_TOKEN = 'Token is invalid or expired';
}
