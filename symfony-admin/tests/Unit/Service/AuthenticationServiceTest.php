<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Enum\AuthRoute;
use App\Security\TokenUser;
use App\Service\AuthenticationService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\User\UserInterface;

final class AuthenticationServiceTest extends TestCase
{
    private AuthenticationService $authenticationService;
    private Security&MockObject $security;
    private UrlGeneratorInterface&MockObject $urlGenerator;

    protected function setUp(): void
    {
        $this->security = $this->createMock(Security::class);
        $this->urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        
        $this->authenticationService = new AuthenticationService(
            $this->security,
            $this->urlGenerator
        );
    }

    public function testGetTokenOrRedirectWithValidTokenUser(): void
    {
        $token = 'valid-jwt-token';
        $tokenUser = new TokenUser($token);

        $this->security
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($tokenUser);

        $result = $this->authenticationService->getTokenOrRedirect();

        $this->assertSame($token, $result);
    }

    public function testGetTokenOrRedirectWithNoUser(): void
    {
        $loginUrl = '/auth/login';

        $this->security
            ->expects($this->once())
            ->method('getUser')
            ->willReturn(null);

        $this->urlGenerator
            ->expects($this->once())
            ->method('generate')
            ->with(AuthRoute::LOGIN->value)
            ->willReturn($loginUrl);

        $result = $this->authenticationService->getTokenOrRedirect();

        $this->assertInstanceOf(RedirectResponse::class, $result);
        $this->assertSame($loginUrl, $result->getTargetUrl());
    }

    public function testGetTokenOrRedirectWithInvalidUser(): void
    {
        $invalidUser = $this->createMock(UserInterface::class);
        $loginUrl = '/auth/login';

        $this->security
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($invalidUser);

        $this->urlGenerator
            ->expects($this->once())
            ->method('generate')
            ->with(AuthRoute::LOGIN->value)
            ->willReturn($loginUrl);

        $result = $this->authenticationService->getTokenOrRedirect();

        $this->assertInstanceOf(RedirectResponse::class, $result);
        $this->assertSame($loginUrl, $result->getTargetUrl());
    }

    public function testIsAuthenticatedWithValidTokenUser(): void
    {
        $token = 'valid-jwt-token';
        $tokenUser = new TokenUser($token);

        $this->security
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($tokenUser);

        $result = $this->authenticationService->isAuthenticated();

        $this->assertTrue($result);
    }

    public function testIsAuthenticatedWithNoUser(): void
    {
        $this->security
            ->expects($this->once())
            ->method('getUser')
            ->willReturn(null);

        $result = $this->authenticationService->isAuthenticated();

        $this->assertFalse($result);
    }

    public function testIsAuthenticatedWithInvalidUser(): void
    {
        $invalidUser = $this->createMock(UserInterface::class);

        $this->security
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($invalidUser);

        $result = $this->authenticationService->isAuthenticated();

        $this->assertFalse($result);
    }

    public function testGetCurrentTokenWithValidTokenUser(): void
    {
        $token = 'valid-jwt-token';
        $tokenUser = new TokenUser($token);

        $this->security
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($tokenUser);

        $result = $this->authenticationService->getCurrentToken();

        $this->assertSame($token, $result);
    }

    public function testGetCurrentTokenWithNoUser(): void
    {
        $this->security
            ->expects($this->once())
            ->method('getUser')
            ->willReturn(null);

        $result = $this->authenticationService->getCurrentToken();

        $this->assertNull($result);
    }

    public function testGetCurrentTokenWithInvalidUser(): void
    {
        $invalidUser = $this->createMock(UserInterface::class);

        $this->security
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($invalidUser);

        $result = $this->authenticationService->getCurrentToken();

        $this->assertNull($result);
    }
}