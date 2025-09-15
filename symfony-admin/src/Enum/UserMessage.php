<?php

declare(strict_types=1);

namespace App\Enum;

enum UserMessage: string
{
    case USER_CREATED = 'Użytkownik został utworzony';
    case USER_UPDATED = 'Dane użytkownika zostały zaktualizowane';
    case USER_DELETED = 'Użytkownik został usunięty';
    case USERS_IMPORTED = 'Import zakończony pomyślnie! Zaimportowano %d użytkowników.';

    case CREATE_FAILED = 'Nie udało się utworzyć użytkownika';
    case UPDATE_FAILED = 'Nie udało się zaktualizować użytkownika';
    case DELETE_FAILED = 'Nie udało się usunąć użytkownika';
    case FETCH_FAILED = 'Nie udało się pobrać danych użytkownika';

    case MAPPING_ERROR = 'Błąd podczas przetwarzania danych użytkownika';
    case VALIDATION_ERROR = 'Błędne dane użytkownika';

    case API_CREATE_ERROR = 'Failed to create user via Phoenix API';
    case API_UPDATE_ERROR = 'Failed to update user via Phoenix API';
    case API_DELETE_ERROR = 'Failed to delete user via Phoenix API';
    case API_FETCH_ERROR = 'Failed to fetch user via Phoenix API';
    case API_FETCH_USERS_ERROR = 'Failed to fetch users for admin panel';
    case API_IMPORT_ERROR = 'Failed to import users via Phoenix API';
}
