<?php

declare(strict_types=1);

namespace App\Enum;

enum AuthRoute: string
{
    case LOGIN = 'app_login';
    case CHECK_AUTH = 'app_check_auth';
    case ADMIN_USERS_INDEX = 'admin_users_index';
}
