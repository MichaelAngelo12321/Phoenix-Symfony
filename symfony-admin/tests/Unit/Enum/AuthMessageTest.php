<?php

declare(strict_types=1);

namespace App\Tests\Unit\Enum;

use App\Enum\AuthMessage;
use PHPUnit\Framework\TestCase;

final class AuthMessageTest extends TestCase
{
    public function testAuthMessageValues(): void
    {
        $this->assertSame('Pomyślnie zalogowano!', AuthMessage::LOGIN_SUCCESS->value);
        $this->assertSame('No token found', AuthMessage::NO_TOKEN->value);
        $this->assertSame('Token is invalid or expired', AuthMessage::INVALID_TOKEN->value);
    }

    public function testAuthMessageCases(): void
    {
        $cases = AuthMessage::cases();
        
        $this->assertCount(3, $cases);
        $this->assertContains(AuthMessage::LOGIN_SUCCESS, $cases);
        $this->assertContains(AuthMessage::NO_TOKEN, $cases);
        $this->assertContains(AuthMessage::INVALID_TOKEN, $cases);
    }

    public function testAuthMessageFromValue(): void
    {
        $this->assertSame(AuthMessage::LOGIN_SUCCESS, AuthMessage::from('Pomyślnie zalogowano!'));
        $this->assertSame(AuthMessage::NO_TOKEN, AuthMessage::from('No token found'));
        $this->assertSame(AuthMessage::INVALID_TOKEN, AuthMessage::from('Token is invalid or expired'));
    }

    public function testAuthMessageTryFromValue(): void
    {
        $this->assertSame(AuthMessage::LOGIN_SUCCESS, AuthMessage::tryFrom('Pomyślnie zalogowano!'));
        $this->assertSame(AuthMessage::NO_TOKEN, AuthMessage::tryFrom('No token found'));
        $this->assertSame(AuthMessage::INVALID_TOKEN, AuthMessage::tryFrom('Token is invalid or expired'));
        $this->assertNull(AuthMessage::tryFrom('invalid'));
        $this->assertNull(AuthMessage::tryFrom(''));
    }

    public function testAuthMessageName(): void
    {
        $this->assertSame('LOGIN_SUCCESS', AuthMessage::LOGIN_SUCCESS->name);
        $this->assertSame('NO_TOKEN', AuthMessage::NO_TOKEN->name);
        $this->assertSame('INVALID_TOKEN', AuthMessage::INVALID_TOKEN->name);
    }
}