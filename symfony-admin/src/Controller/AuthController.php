<?php

declare(strict_types=1);

namespace App\Controller;

use App\Constants\AuthConstants;
use App\Form\LoginFormType;
use App\Service\PhoenixAuthServiceInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class AuthController extends AbstractController
{
    public function __construct(
        private readonly PhoenixAuthServiceInterface $phoenixAuthService,
        private readonly RequestStack $requestStack,
        private readonly LoggerInterface $logger
    ) {
    }

    #[Route('/login', name: AuthConstants::ROUTE_LOGIN)]
    public function login(Request $request): Response
    {
        $session = $this->requestStack->getSession();
        if ($session->has(AuthConstants::SESSION_ADMIN_TOKEN)) {
            return $this->redirectToRoute(AuthConstants::ROUTE_ADMIN_USERS_INDEX);
        }

        $form = $this->createForm(LoginFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            try {
                $result = $this->phoenixAuthService->login($data['email'], $data['password']);

                if ($result->isSuccess()) {
                    $session->migrate(true); // Regenerate session ID for security
                    $session->set(AuthConstants::SESSION_ADMIN_TOKEN, $result->token);
                    $session->set(AuthConstants::SESSION_ADMIN_DATA, $result->admin);

                    $this->logger->info('Admin login successful', [
                        'email' => $data['email'],
                        'ip' => $request->getClientIp(),
                    ]);

                    $this->addFlash(AuthConstants::FLASH_SUCCESS, AuthConstants::MESSAGE_LOGIN_SUCCESS);
                    return $this->redirectToRoute(AuthConstants::ROUTE_ADMIN_USERS_INDEX);
                }

                $this->logger->warning('Admin login failed', [
                    'email' => $data['email'],
                    'error' => $result->error,
                    'ip' => $request->getClientIp(),
                ]);

                $this->addFlash(AuthConstants::FLASH_ERROR, $result->error);
            } catch (\Exception $e) {
                $this->logger->error('Login process failed', [
                    'email' => $data['email'],
                    'error' => $e->getMessage(),
                    'ip' => $request->getClientIp(),
                ]);

                $this->addFlash(AuthConstants::FLASH_ERROR, 'Wystąpił błąd podczas logowania. Spróbuj ponownie.');
            }
        }

        return $this->render('auth/login.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/check-auth', name: AuthConstants::ROUTE_CHECK_AUTH)]
    public function checkAuth(): Response
    {
        $session = $this->requestStack->getSession();
        $token = $session->get(AuthConstants::SESSION_ADMIN_TOKEN);

        if (! $token) {
            return $this->json([
                'authenticated' => false,
                'message' => AuthConstants::MESSAGE_NO_TOKEN,
            ]);
        }

        try {
            $result = $this->phoenixAuthService->verifyToken($token);

            if (! $result->isValid()) {
                $session->remove(AuthConstants::SESSION_ADMIN_TOKEN);
                $session->remove(AuthConstants::SESSION_ADMIN_DATA);

                $this->logger->info('Token verification failed - session cleared', [
                    'error' => $result->error,
                ]);

                return $this->json([
                    'authenticated' => false,
                    'message' => AuthConstants::MESSAGE_INVALID_TOKEN,
                ]);
            }

            return $this->json([
                'authenticated' => true,
                'admin' => $result->admin,
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Token verification process failed', [
                'error' => $e->getMessage(),
            ]);

            $session->remove(AuthConstants::SESSION_ADMIN_TOKEN);
            $session->remove(AuthConstants::SESSION_ADMIN_DATA);

            return $this->json([
                'authenticated' => false,
                'message' => 'Verification failed due to system error',
            ]);
        }
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(): Response
    {
        $session = $this->requestStack->getSession();
        $session->remove(AuthConstants::SESSION_ADMIN_TOKEN);
        $session->remove(AuthConstants::SESSION_ADMIN_DATA);

        $this->logger->info('Admin logout successful');

        $this->addFlash(AuthConstants::FLASH_SUCCESS, 'Pomyślnie wylogowano!');
        return $this->redirectToRoute(AuthConstants::ROUTE_LOGIN);
    }
}
