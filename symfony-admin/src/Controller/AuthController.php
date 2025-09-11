<?php

declare(strict_types=1);

namespace App\Controller;

use App\Form\LoginFormType;
use App\Service\PhoenixAuthServiceInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Attribute\Route;

final class AuthController extends AbstractController
{
    public function __construct(
        private readonly PhoenixAuthServiceInterface $phoenixAuthService
    ) {
    }

    #[Route('/login', name: 'app_login')]
    public function login(Request $request, SessionInterface $session): Response
    {
        if ($session->has('admin_token')) {
            return $this->redirectToRoute('admin_users_index');
        }

        $form = $this->createForm(LoginFormType::class);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            $result = $this->phoenixAuthService->login($data['email'], $data['password']);

            if ($result['success']) {
                $session->set('admin_token', $result['token']);
                $session->set('admin_data', $result['admin']);

                $this->addFlash('success', 'Pomyślnie zalogowano!');
                return $this->redirectToRoute('admin_users_index');
            }
            $this->addFlash('error', $result['error']);
        }

        return $this->render('auth/login.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/check-auth', name: 'app_check_auth')]
    public function checkAuth(SessionInterface $session): Response
    {
        $token = $session->get('admin_token');

        if (! $token) {
            return $this->json([
                'authenticated' => false,
                'message' => 'No token found',
            ]);
        }

        $result = $this->phoenixAuthService->verifyToken($token);

        if (! $result['success'] || ! $result['valid']) {
            $session->remove('admin_token');
            $session->remove('admin_data');

            return $this->json([
                'authenticated' => false,
                'message' => 'Token is invalid or expired',
            ]);
        }

        return $this->json([
            'authenticated' => true,
            'admin' => $result['admin'],
        ]);
    }
}
