<?php

declare(strict_types=1);

namespace App\Tests\Unit\Enum;

use App\Enum\UserMessage;
use PHPUnit\Framework\TestCase;

final class UserMessageTest extends TestCase
{
    public function testSuccessMessages(): void
    {
        $this->assertSame('Użytkownik został utworzony', UserMessage::USER_CREATED->value);
        $this->assertSame('Dane użytkownika zostały zaktualizowane', UserMessage::USER_UPDATED->value);
        $this->assertSame('Użytkownik został usunięty', UserMessage::USER_DELETED->value);
        $this->assertSame('Import zakończony pomyślnie! Zaimportowano %d użytkowników.', UserMessage::USERS_IMPORTED->value);
    }

    public function testErrorMessages(): void
    {
        $this->assertSame('Nie udało się utworzyć użytkownika', UserMessage::CREATE_FAILED->value);
        $this->assertSame('Nie udało się zaktualizować użytkownika', UserMessage::UPDATE_FAILED->value);
        $this->assertSame('Nie udało się usunąć użytkownika', UserMessage::DELETE_FAILED->value);
        $this->assertSame('Nie udało się pobrać danych użytkownika', UserMessage::FETCH_FAILED->value);
    }

    public function testValidationMessages(): void
    {
        $this->assertSame('Błąd podczas przetwarzania danych użytkownika', UserMessage::MAPPING_ERROR->value);
        $this->assertSame('Błędne dane użytkownika', UserMessage::VALIDATION_ERROR->value);
    }

    public function testApiErrorMessages(): void
    {
        $this->assertSame('Failed to create user via Phoenix API', UserMessage::API_CREATE_ERROR->value);
        $this->assertSame('Failed to update user via Phoenix API', UserMessage::API_UPDATE_ERROR->value);
        $this->assertSame('Failed to delete user via Phoenix API', UserMessage::API_DELETE_ERROR->value);
        $this->assertSame('Failed to fetch user via Phoenix API', UserMessage::API_FETCH_ERROR->value);
        $this->assertSame('Failed to fetch users for admin panel', UserMessage::API_FETCH_USERS_ERROR->value);
        $this->assertSame('Failed to import users via Phoenix API', UserMessage::API_IMPORT_ERROR->value);
    }

    public function testAllCasesAreDefined(): void
    {
        $cases = UserMessage::cases();

        $this->assertCount(16, $cases);

        $expectedCases = [
            UserMessage::USER_CREATED,
            UserMessage::USER_UPDATED,
            UserMessage::USER_DELETED,
            UserMessage::USERS_IMPORTED,
            UserMessage::CREATE_FAILED,
            UserMessage::UPDATE_FAILED,
            UserMessage::DELETE_FAILED,
            UserMessage::FETCH_FAILED,
            UserMessage::MAPPING_ERROR,
            UserMessage::VALIDATION_ERROR,
            UserMessage::API_CREATE_ERROR,
            UserMessage::API_UPDATE_ERROR,
            UserMessage::API_DELETE_ERROR,
            UserMessage::API_FETCH_ERROR,
            UserMessage::API_FETCH_USERS_ERROR,
            UserMessage::API_IMPORT_ERROR,
        ];

        foreach ($expectedCases as $expectedCase) {
            $this->assertContains($expectedCase, $cases);
        }
    }

    public function testTryFromWithValidValues(): void
    {
        $this->assertSame(UserMessage::USER_CREATED, UserMessage::tryFrom('Użytkownik został utworzony'));
        $this->assertSame(UserMessage::CREATE_FAILED, UserMessage::tryFrom('Nie udało się utworzyć użytkownika'));
        $this->assertSame(UserMessage::API_CREATE_ERROR, UserMessage::tryFrom('Failed to create user via Phoenix API'));
    }

    public function testTryFromWithInvalidValue(): void
    {
        $this->assertNull(UserMessage::tryFrom('Invalid message'));
    }

    public function testFromWithValidValues(): void
    {
        $this->assertSame(UserMessage::USER_CREATED, UserMessage::from('Użytkownik został utworzony'));
        $this->assertSame(UserMessage::CREATE_FAILED, UserMessage::from('Nie udało się utworzyć użytkownika'));
    }

    public function testFromWithInvalidValueThrowsException(): void
    {
        $this->expectException(\ValueError::class);
        UserMessage::from('Invalid message');
    }

    public function testEnumIsBackedByString(): void
    {
        $reflection = new \ReflectionEnum(UserMessage::class);

        $this->assertTrue($reflection->isBacked());
        $this->assertSame('string', $reflection->getBackingType()->getName());
    }

    public function testMessageLanguages(): void
    {
        $polishMessages = [
            UserMessage::USER_CREATED,
            UserMessage::USER_UPDATED,
            UserMessage::USER_DELETED,
            UserMessage::USERS_IMPORTED,
            UserMessage::CREATE_FAILED,
            UserMessage::UPDATE_FAILED,
            UserMessage::DELETE_FAILED,
            UserMessage::FETCH_FAILED,
            UserMessage::MAPPING_ERROR,
            UserMessage::VALIDATION_ERROR,
        ];

        $englishMessages = [
            UserMessage::API_CREATE_ERROR,
            UserMessage::API_UPDATE_ERROR,
            UserMessage::API_DELETE_ERROR,
            UserMessage::API_FETCH_ERROR,
            UserMessage::API_FETCH_USERS_ERROR,
            UserMessage::API_IMPORT_ERROR,
        ];

        foreach ($polishMessages as $message) {
            $hasPolishChars = preg_match('/[ąćęłńóśźżĄĆĘŁŃÓŚŹŻ]/', $message->value);
            $this->assertTrue($hasPolishChars > 0, 'Polish message should contain Polish characters: ' . $message->value);
        }

        foreach ($englishMessages as $message) {
            $this->assertStringStartsWith('Failed to', $message->value, 'API error message should start with "Failed to"');
        }
    }

    public function testUsersImportedMessageHasPlaceholder(): void
    {
        $this->assertStringContainsString('%d', UserMessage::USERS_IMPORTED->value);
    }
}
