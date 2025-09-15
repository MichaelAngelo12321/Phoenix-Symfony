<?php

declare(strict_types=1);

namespace App\Enum;

enum AuthMessage: string
{
    case LOGIN_SUCCESS = 'Pomyślnie zalogowano!';
    case NO_TOKEN = 'No token found';
    case INVALID_TOKEN = 'Token is invalid or expired';
}
