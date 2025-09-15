<?php

declare(strict_types=1);

namespace App\Enum;

enum ValidatorMessage: string
{
    case FIRST_NAME_REQUIRED = 'Imię jest wymagane';
    case FIRST_NAME_TOO_SHORT = 'Imię musi mieć co najmniej 2 znaki';
    case FIRST_NAME_TOO_LONG = 'Imię nie może być dłuższe niż {{ limit }} znaków';

    case LAST_NAME_REQUIRED = 'Nazwisko jest wymagane';
    case LAST_NAME_TOO_SHORT = 'Nazwisko musi mieć co najmniej 2 znaki';
    case LAST_NAME_TOO_LONG = 'Nazwisko nie może być dłuższe niż {{ limit }} znaków';

    case BIRTHDATE_REQUIRED = 'Data urodzenia jest wymagana';
    case BIRTHDATE_FUTURE = 'Data urodzenia nie może być z przyszłości';
    case BIRTHDATE_TOO_OLD = 'Data urodzenia nie może być wcześniejsza niż 1900 rok';
    case DATE_FUTURE = 'Data nie może być z przyszłości';

    case GENDER_REQUIRED = 'Płeć jest wymagana';

    case PASSWORD_REQUIRED = 'Hasło jest wymagane';

    case LOGIN_REQUIRED = 'Musisz się zalogować, aby uzyskać dostęp do tej strony.';
}
