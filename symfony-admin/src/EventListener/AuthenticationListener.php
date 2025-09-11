<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Service\PhoenixAuthService;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class AuthenticationListener
{
    private PhoenixAuthService $phoenixAuthService;
    private UrlGeneratorInterface $urlGenerator;
    private array $publicRoutes = [
        'app_login',
        'app_logout',
        'app_check_auth',
    ];

    public function __construct(
        PhoenixAuthService $phoenixAuthService,
        UrlGeneratorInterface $urlGenerator
    ) {
        $this->phoenixAuthService = $phoenixAuthService;
        $this->urlGenerator = $urlGenerator;
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();
        $session = $request->getSession();

        // Skip authentication for public routes
        $routeName = $request->attributes->get('_route');
        if (in_array($routeName, $this->publicRoutes) || ! $routeName) {
            return;
        }

        // Skip authentication for non-admin routes (if any)
        if (! str_starts_with($routeName, 'admin_') && $routeName !== 'app_check_auth') {
            return;
        }

        $token = $session->get('admin_token');

        // No token found, redirect to login
        if (! $token) {
            $loginUrl = $this->urlGenerator->generate('app_login');
            $response = new RedirectResponse($loginUrl);
            $event->setResponse($response);
            return;
        }

        // Verify token with Phoenix API
        $result = $this->phoenixAuthService->verifyToken($token);

        if (! $result['success'] || ! $result['valid']) {
            // Token is invalid, clear session and redirect to login
            $session->remove('admin_token');
            $session->remove('admin_data');

            $loginUrl = $this->urlGenerator->generate('app_login');
            $response = new RedirectResponse($loginUrl);
            $event->setResponse($response);
            return;
        }

        // Update admin data in session if provided
        if (isset($result['admin'])) {
            $session->set('admin_data', $result['admin']);
        }
    }
}
