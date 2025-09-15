<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security;

use App\Security\TokenUser;
use App\Security\TokenUserProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\UserInterface;

final class TokenUserProviderTest extends TestCase
{
    private TokenUserProvider $provider;

    protected function setUp(): void
    {
        $this->provider = new TokenUserProvider();
    }

    public function testRefreshUserWithTokenUser(): void
    {
        $token = 'test-token';
        $user = new TokenUser($token);

        $refreshedUser = $this->provider->refreshUser($user);

        $this->assertSame($user, $refreshedUser);
        $this->assertSame($token, $refreshedUser->getUserIdentifier());
    }

    public function testRefreshUserWithInvalidUserThrowsException(): void
    {
        $invalidUser = $this->createMock(UserInterface::class);

        $this->expectException(UnsupportedUserException::class);
        $this->expectExceptionMessage('Invalid user class "' . $invalidUser::class . '".');

        $this->provider->refreshUser($invalidUser);
    }

    public function testSupportsClassWithTokenUser(): void
    {
        $this->assertTrue($this->provider->supportsClass(TokenUser::class));
    }

    public function testSupportsClassWithTokenUserSubclass(): void
    {
        $result = $this->provider->supportsClass(TokenUser::class);

        $this->assertTrue($result);
    }

    public function testSupportsClassWithInvalidClass(): void
    {
        $this->assertFalse($this->provider->supportsClass(UserInterface::class));
        $this->assertFalse($this->provider->supportsClass('InvalidClass'));
        $this->assertFalse($this->provider->supportsClass(\stdClass::class));
    }

    public function testLoadUserByIdentifier(): void
    {
        $identifier = 'test-identifier';

        $user = $this->provider->loadUserByIdentifier($identifier);

        $this->assertInstanceOf(TokenUser::class, $user);
        $this->assertSame($identifier, $user->getUserIdentifier());
        $this->assertSame($identifier, $user->getUserIdentifier());
    }

    public function testLoadUserByIdentifierWithEmptyString(): void
    {
        $user = $this->provider->loadUserByIdentifier('');

        $this->assertInstanceOf(TokenUser::class, $user);
        $this->assertSame('', $user->getUserIdentifier());
    }

    public function testLoadUserByIdentifierWithSpecialCharacters(): void
    {
        $identifier = 'token-with-special!@#$%';

        $user = $this->provider->loadUserByIdentifier($identifier);

        $this->assertInstanceOf(TokenUser::class, $user);
        $this->assertSame($identifier, $user->getUserIdentifier());
    }

    public function testLoadUserByIdentifierAlwaysCreatesNewInstance(): void
    {
        $identifier = 'test-token';

        $user1 = $this->provider->loadUserByIdentifier($identifier);
        $user2 = $this->provider->loadUserByIdentifier($identifier);

        $this->assertNotSame($user1, $user2);
        $this->assertEquals($user1->getUserIdentifier(), $user2->getUserIdentifier());
    }
}
