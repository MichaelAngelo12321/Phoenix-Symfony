<?php

namespace App\Controller;

use App\Service\PhoenixAuthService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Validator\Constraints as Assert;

class AuthController extends AbstractController
{
    private PhoenixAuthService $phoenixAuthService;

    public function __construct(PhoenixAuthService $phoenixAuthService)
    {
        $this->phoenixAuthService = $phoenixAuthService;
    }

    #[Route('/login', name: 'app_login')]
    public function login(Request $request, SessionInterface $session): Response
    {
        // Redirect if already logged in
        if ($session->has('admin_token')) {
            return $this->redirectToRoute('admin_users_index');
        }

        $form = $this->createFormBuilder()
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Email jest wymagany']),
                    new Assert\Email(['message' => 'Podaj prawidłowy adres email'])
                ],
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'admin@example.com'
                ]
            ])
            ->add('password', PasswordType::class, [
                'label' => 'Hasło',
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Hasło jest wymagane'])
                ],
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Wprowadź hasło'
                ]
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Zaloguj się',
                'attr' => ['class' => 'btn btn-primary w-100']
            ])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            
            $result = $this->phoenixAuthService->login($data['email'], $data['password']);
            
            if ($result['success']) {
                // Store token and admin data in session
                $session->set('admin_token', $result['token']);
                $session->set('admin_data', $result['admin']);
                
                $this->addFlash('success', 'Pomyślnie zalogowano!');
                return $this->redirectToRoute('admin_users_index');
            } else {
                $this->addFlash('error', $result['error']);
            }
        }

        return $this->render('auth/login.html.twig', [
            'form' => $form->createView()
        ]);
    }



    #[Route('/check-auth', name: 'app_check_auth')]
    public function checkAuth(SessionInterface $session): Response
    {
        $token = $session->get('admin_token');
        
        if (!$token) {
            return $this->json([
                'authenticated' => false,
                'message' => 'No token found'
            ]);
        }

        $result = $this->phoenixAuthService->verifyToken($token);
        
        if (!$result['success'] || !$result['valid']) {
            // Token is invalid, clear session
            $session->remove('admin_token');
            $session->remove('admin_data');
            
            return $this->json([
                'authenticated' => false,
                'message' => 'Token is invalid or expired'
            ]);
        }

        return $this->json([
            'authenticated' => true,
            'admin' => $result['admin']
        ]);
    }
}