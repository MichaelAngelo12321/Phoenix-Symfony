<?php

declare(strict_types=1);

namespace App\Service;

use App\Enum\AuthRoute;
use App\Security\TokenUser;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final readonly class AuthenticationService implements AuthenticationServiceInterface
{
    public function __construct(
        private Security $security,
        private UrlGeneratorInterface $urlGenerator
    ) {
    }

    public function getTokenOrRedirect(): string|RedirectResponse
    {
        $user = $this->security->getUser();

        if (! $user instanceof TokenUser) {
            return new RedirectResponse(
                $this->urlGenerator->generate(AuthRoute::LOGIN->value)
            );
        }

        return $user->getToken();
    }

    public function isAuthenticated(): bool
    {
        return $this->security->getUser() instanceof TokenUser;
    }

    public function getCurrentToken(): ?string
    {
        $user = $this->security->getUser();

        return $user instanceof TokenUser ? $user->getToken() : null;
    }
}
