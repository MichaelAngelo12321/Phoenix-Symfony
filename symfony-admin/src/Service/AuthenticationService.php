<?php

declare(strict_types=1);

namespace App\Service;

use App\Constants\AuthConstants;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final readonly class AuthenticationService implements AuthenticationServiceInterface
{
    public function __construct(
        private RequestStack $requestStack,
        private UrlGeneratorInterface $urlGenerator
    ) {
    }

    public function getTokenOrRedirect(): string|RedirectResponse
    {
        $session = $this->requestStack->getSession();
        $token = $session->get(AuthConstants::SESSION_ADMIN_TOKEN);

        if (! $token) {
            $session->getFlashBag()->add('error', 'Musisz się zalogować, aby uzyskać dostęp do tej strony.');
            return new RedirectResponse(
                $this->urlGenerator->generate(AuthConstants::ROUTE_LOGIN)
            );
        }

        return $token;
    }

    public function isAuthenticated(): bool
    {
        $session = $this->requestStack->getSession();
        return $session->has(AuthConstants::SESSION_ADMIN_TOKEN);
    }

    public function getCurrentToken(): ?string
    {
        $session = $this->requestStack->getSession();
        return $session->get(AuthConstants::SESSION_ADMIN_TOKEN);
    }
}
