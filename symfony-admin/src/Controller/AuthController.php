<?php

declare(strict_types=1);

namespace App\Controller;

use App\Enum\AuthMessage;
use App\Enum\AuthRoute;
use App\Enum\FlashType;
use App\Form\LoginFormType;
use App\Security\TokenUser;
use App\Service\PhoenixAuthServiceInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class AuthController extends AbstractController
{
    public function __construct(
        private readonly PhoenixAuthServiceInterface $phoenixAuthService,
        private readonly LoggerInterface $logger,
        private readonly Security $security,
    ) {
    }

    #[Route('/login', name: AuthRoute::LOGIN->value)]
    public function login(Request $request): Response
    {
        if ($this->security->getUser() instanceof TokenUser) {
            return $this->redirectToRoute(AuthRoute::ADMIN_USERS_INDEX->value);
        }

        $form = $this->createForm(LoginFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            try {
                $result = $this->phoenixAuthService->login($data['email'], $data['password']);

                if ($result->isSuccess()) {
                    $request->getSession()->migrate(true);
                    $request->getSession()->set('admin_token', $result->token);

                    $this->logger->info('Admin login successful', [
                        'email' => $data['email'],
                        'ip' => $request->getClientIp(),
                    ]);

                    $this->addFlash(FlashType::SUCCESS->value, AuthMessage::LOGIN_SUCCESS->value);
                    return $this->redirectToRoute(AuthRoute::ADMIN_USERS_INDEX->value);
                }

                $this->logger->warning('Admin login failed', [
                    'email' => $data['email'],
                    'error' => $result->error,
                    'ip' => $request->getClientIp(),
                ]);

                $this->addFlash(FlashType::ERROR->value, $result->error);
            } catch (\Exception $e) {
                $this->logger->error('Login process failed', [
                    'email' => $data['email'],
                    'error' => $e->getMessage(),
                    'ip' => $request->getClientIp(),
                ]);

                $this->addFlash(FlashType::ERROR->value, 'Wystąpił błąd podczas logowania. Spróbuj ponownie.');
            }
        }

        return $this->render('auth/login.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
