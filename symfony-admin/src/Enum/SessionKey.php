<?php

declare(strict_types=1);

namespace App\Enum;

enum SessionKey: string
{
    case ADMIN_TOKEN = 'admin_token';
    case ADMIN_DATA = 'admin_data';
}
