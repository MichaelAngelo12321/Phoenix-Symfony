<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Constants\AuthConstants;
use App\Service\PhoenixAuthServiceInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class AuthenticationListener
{
    private array $publicRoutes = [
        AuthConstants::ROUTE_LOGIN,
        AuthConstants::ROUTE_LOGOUT,
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
        $token = $session->get(AuthConstants::SESSION_ADMIN_TOKEN);

        if (! $token) {
            $loginUrl = $this->urlGenerator->generate(AuthConstants::ROUTE_LOGIN);
            $response = new RedirectResponse($loginUrl);
            $event->setResponse($response);
            return;
        }

        $result = $this->phoenixAuthService->verifyToken($token);

        if (! $result->isValid()) {
            $session->remove(AuthConstants::SESSION_ADMIN_TOKEN);
            $session->remove(AuthConstants::SESSION_ADMIN_DATA);

            $loginUrl = $this->urlGenerator->generate(AuthConstants::ROUTE_LOGIN);
            $response = new RedirectResponse($loginUrl);
            $event->setResponse($response);
            return;
        }

        if ($result->admin) {
            $session->set(AuthConstants::SESSION_ADMIN_DATA, $result->admin);
        }
    }
}
