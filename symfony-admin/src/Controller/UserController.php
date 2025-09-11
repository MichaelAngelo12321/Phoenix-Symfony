<?php

declare(strict_types=1);

namespace App\Controller;

use App\Form\UserFormType;
use App\Service\UserServiceInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Attribute\Route;

/**
 * User management controller for admin panel
 *
 * Handles CRUD operations for users by communicating with Phoenix API
 */
#[Route('/admin/users', name: 'admin_users_')]
final class UserController extends AbstractController
{
    public function __construct(
        private readonly UserServiceInterface $userService
    ) {
    }

    /**
     * Display list of all users with filtering and sorting
     */
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(Request $request, SessionInterface $session): Response
    {
        $token = $this->getTokenOrRedirect($session);
        if ($token instanceof Response) {
            return $token;
        }

        $result = $this->userService->getUsers($token, $request);

        foreach ($result['errors'] as $error) {
            $this->addFlash('error', $error);
        }

        return $this->render('admin/users/index.html.twig', [
            'users' => $result['users'],
            'api_available' => $result['api_available'],
            'current_filters' => $result['current_filters'],
            'sort_by' => $result['sort_by'],
            'sort_order' => $result['sort_order'],
        ]);
    }

    /**
     * Show user details
     */
    #[Route('/{id}', name: 'show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(int $id, SessionInterface $session): Response
    {
        $token = $this->getTokenOrRedirect($session);
        if ($token instanceof Response) {
            return $token;
        }

        $result = $this->userService->getUser($token, $id);

        foreach ($result['errors'] as $error) {
            $this->addFlash('error', $error);
        }

        if (! $result['user']) {
            return $this->redirectToRoute('admin_users_index');
        }

        return $this->render('admin/users/show.html.twig', [
            'user' => $result['user'],
            'api_available' => $result['api_available'],
        ]);
    }

    /**
     * Show form for creating new user
     */
    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, SessionInterface $session): Response
    {
        $token = $this->getTokenOrRedirect($session);
        if ($token instanceof Response) {
            return $token;
        }

        $form = $this->createForm(UserFormType::class, null, ['is_edit' => false]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $userData = $form->getData();
            $result = $this->userService->createUser($token, $userData);

            foreach ($result['errors'] as $error) {
                $this->addFlash('error', $error);
            }

            if ($result['success']) {
                $this->addFlash('success', 'Użytkownik został pomyślnie utworzony.');
                return $this->redirectToRoute('admin_users_show', ['id' => $result['user']['id']]);
            }
        }

        return $this->render('admin/users/new.html.twig', [
            'form' => $form,
        ]);
    }

    /**
     * Show form for editing user
     */
    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(int $id, Request $request, SessionInterface $session): Response
    {
        $token = $this->getTokenOrRedirect($session);
        if ($token instanceof Response) {
            return $token;
        }

        $result = $this->userService->getUser($token, $id);

        foreach ($result['errors'] as $error) {
            $this->addFlash('error', $error);
        }

        if (! $result['user']) {
            return $this->redirectToRoute('admin_users_index');
        }

        $userData = $result['user'];

        // Convert birthdate string to DateTime for form compatibility
        if (isset($userData['birthdate']) && is_string($userData['birthdate'])) {
            $userData['birthdate'] = new \DateTime($userData['birthdate']);
        }

        $form = $this->createForm(UserFormType::class, $userData, ['is_edit' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $userData = $form->getData();
            $updateResult = $this->userService->updateUser($token, $id, $userData);

            foreach ($updateResult['errors'] as $error) {
                $this->addFlash('error', $error);
            }

            if ($updateResult['success']) {
                $this->addFlash('success', 'Użytkownik został pomyślnie zaktualizowany.');
                return $this->redirectToRoute('admin_users_show', ['id' => $id]);
            }
        }

        return $this->render('admin/users/edit.html.twig', [
            'user' => $result['user'],
            'form' => $form,
        ]);
    }

    /**
     * Handle user deletion
     */
    #[Route('/{id}/delete', name: 'delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(int $id, SessionInterface $session): Response
    {
        $token = $this->getTokenOrRedirect($session);
        if ($token instanceof Response) {
            return $token;
        }

        $result = $this->userService->deleteUser($token, $id);

        foreach ($result['errors'] as $error) {
            $this->addFlash('error', $error);
        }

        if ($result['success']) {
            $this->addFlash('success', 'Użytkownik został pomyślnie usunięty.');
        }

        return $this->redirectToRoute('admin_users_index');
    }

    /**
     * Import users from external API
     */
    #[Route('/import', name: 'import', methods: ['POST'])]
    public function import(SessionInterface $session): Response
    {
        $token = $this->getTokenOrRedirect($session);
        if ($token instanceof Response) {
            return $token;
        }

        $result = $this->userService->importUsers($token);

        foreach ($result['errors'] as $error) {
            $this->addFlash('error', $error);
        }

        if ($result['success']) {
            $this->addFlash('success', sprintf(
                'Import zakończony pomyślnie! Zaimportowano %d użytkowników.',
                $result['count'] ?? 0
            ));
        }

        return $this->redirectToRoute('admin_users_index');
    }

    /**
     * API status check endpoint
     */
    #[Route('/api-status', name: 'api_status', methods: ['GET'])]
    public function apiStatus(): JsonResponse
    {
        $result = $this->userService->checkApiStatus();

        return $this->json([
            'available' => $result['available'],
            'timestamp' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Get JWT token from session or redirect to login
     */
    private function getTokenOrRedirect(SessionInterface $session): string|Response
    {
        $token = $session->get('admin_token');

        if (! $token) {
            $this->addFlash('error', 'Musisz się zalogować, aby uzyskać dostęp do tej strony.');
            return $this->redirectToRoute('app_login');
        }

        return $token;
    }
}
