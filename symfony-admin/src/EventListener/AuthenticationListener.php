<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Enum\AuthRoute;
use App\Enum\SessionKey;
use App\Service\PhoenixAuthServiceInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class AuthenticationListener
{
    private array $publicRoutes = [
        AuthRoute::LOGIN->value,
        'app_check_auth',
    ];

    public function __construct(
        private readonly PhoenixAuthServiceInterface $phoenixAuthService,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly RequestStack $requestStack,
    ) {
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();

        $routeName = $request->attributes->get('_route');
        if (in_array($routeName, $this->publicRoutes) || ! $routeName) {
            return;
        }

        if (! str_starts_with($routeName, 'admin_') && $routeName !== 'app_check_auth') {
            return;
        }

        $session = $this->requestStack->getSession();
        $token = $session->get(SessionKey::ADMIN_TOKEN->value);

        if (! $token) {
            $loginUrl = $this->urlGenerator->generate(AuthRoute::LOGIN->value);
            $response = new RedirectResponse($loginUrl);
            $event->setResponse($response);
            return;
        }

        $result = $this->phoenixAuthService->verifyToken($token);

        if (! $result->isValid()) {
            $session->remove(SessionKey::ADMIN_TOKEN->value);
            $session->remove(SessionKey::ADMIN_DATA->value);

            $loginUrl = $this->urlGenerator->generate(AuthRoute::LOGIN->value);
            $response = new RedirectResponse($loginUrl);
            $event->setResponse($response);
            return;
        }

        if ($result->admin) {
            $session->set(SessionKey::ADMIN_DATA->value, $result->admin);
        }
    }
}
