<?php

declare(strict_types=1);

namespace App\Tests\Unit\Enum;

use App\Enum\SessionKey;
use PHPUnit\Framework\TestCase;

final class SessionKeyTest extends TestCase
{
    public function testSessionKeyValues(): void
    {
        $this->assertSame('admin_token', SessionKey::ADMIN_TOKEN->value);
        $this->assertSame('admin_data', SessionKey::ADMIN_DATA->value);
    }

    public function testSessionKeyCases(): void
    {
        $cases = SessionKey::cases();
        
        $this->assertCount(2, $cases);
        $this->assertContains(SessionKey::ADMIN_TOKEN, $cases);
        $this->assertContains(SessionKey::ADMIN_DATA, $cases);
    }

    public function testSessionKeyFromValue(): void
    {
        $this->assertSame(SessionKey::ADMIN_TOKEN, SessionKey::from('admin_token'));
        $this->assertSame(SessionKey::ADMIN_DATA, SessionKey::from('admin_data'));
    }

    public function testSessionKeyTryFromValue(): void
    {
        $this->assertSame(SessionKey::ADMIN_TOKEN, SessionKey::tryFrom('admin_token'));
        $this->assertSame(SessionKey::ADMIN_DATA, SessionKey::tryFrom('admin_data'));
        $this->assertNull(SessionKey::tryFrom('invalid'));
        $this->assertNull(SessionKey::tryFrom(''));
    }

    public function testSessionKeyName(): void
    {
        $this->assertSame('ADMIN_TOKEN', SessionKey::ADMIN_TOKEN->name);
        $this->assertSame('ADMIN_DATA', SessionKey::ADMIN_DATA->name);
    }
}