<?php

namespace App\Controller;

use App\Service\PhoenixApiService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Psr\Log\LoggerInterface;

/**
 * User management controller for admin panel
 * 
 * Handles CRUD operations for users by communicating with Phoenix API
 */
#[Route('/admin/users', name: 'admin_users_')]
class UserController extends AbstractController
{
    public function __construct(
        private readonly PhoenixApiService $phoenixApiService,
        private readonly LoggerInterface $logger
    ) {}

    /**
     * Display list of all users
     */
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(): Response
    {
        try {
            $apiResponse = $this->phoenixApiService->getUsers();
            $users = $apiResponse['data'] ?? [];
            
            return $this->render('admin/users/index.html.twig', [
                'users' => $users,
                'api_available' => true,
            ]);
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to fetch users for admin panel', [
                'error' => $e->getMessage()
            ]);
            
            $this->addFlash('error', 'Nie można pobrać listy użytkowników: ' . $e->getMessage());
            
            return $this->render('admin/users/index.html.twig', [
                'users' => [],
                'api_available' => false,
            ]);
        }
    }

    /**
     * Show user details
     */
    #[Route('/{id}', name: 'show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(int $id): Response
    {
        try {
            $apiResponse = $this->phoenixApiService->getUser($id);
            $user = $apiResponse['data'] ?? null;
            
            if (!$user) {
                throw $this->createNotFoundException('Użytkownik nie został znaleziony.');
            }
            
            return $this->render('admin/users/show.html.twig', [
                'user' => $user,
            ]);
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to fetch user details for admin panel', [
                'user_id' => $id,
                'error' => $e->getMessage()
            ]);
            
            $this->addFlash('error', 'Nie można pobrać danych użytkownika: ' . $e->getMessage());
            
            return $this->redirectToRoute('admin_users_index');
        }
    }

    /**
     * Show form for creating new user
     */
    #[Route('/new', name: 'new', methods: ['GET'])]
    public function new(): Response
    {
        return $this->render('admin/users/new.html.twig');
    }

    /**
     * Handle user creation
     */
    #[Route('/create', name: 'create', methods: ['POST'])]
    public function create(Request $request): Response
    {
        $userData = [
            'first_name' => $request->request->get('first_name'),
            'last_name' => $request->request->get('last_name'),
            'birthdate' => $request->request->get('birthdate'),
            'gender' => $request->request->get('gender'),
        ];
        
        // Basic validation
        $errors = [];
        if (empty($userData['first_name'])) {
            $errors[] = 'Imię jest wymagane';
        }
        if (empty($userData['last_name'])) {
            $errors[] = 'Nazwisko jest wymagane';
        }
        if (empty($userData['birthdate'])) {
            $errors[] = 'Data urodzenia jest wymagana';
        }
        if (!in_array($userData['gender'], ['male', 'female'])) {
            $errors[] = 'Płeć musi być "male" lub "female"';
        }
        
        if (!empty($errors)) {
            foreach ($errors as $error) {
                $this->addFlash('error', $error);
            }
            return $this->render('admin/users/new.html.twig', [
                'form_data' => $userData
            ]);
        }
        
        try {
            $apiResponse = $this->phoenixApiService->createUser($userData);
            $user = $apiResponse['data'] ?? null;
            
            if ($user) {
                $this->addFlash('success', 'Użytkownik został pomyślnie utworzony.');
                return $this->redirectToRoute('admin_users_show', ['id' => $user['id']]);
            }
            
            throw new \Exception('Nie otrzymano danych utworzonego użytkownika');
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to create user via admin panel', [
                'user_data' => $userData,
                'error' => $e->getMessage()
            ]);
            
            $this->addFlash('error', 'Nie można utworzyć użytkownika: ' . $e->getMessage());
            
            return $this->render('admin/users/new.html.twig', [
                'form_data' => $userData
            ]);
        }
    }

    /**
     * Show form for editing user
     */
    #[Route('/{id}/edit', name: 'edit', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function edit(int $id): Response
    {
        try {
            $apiResponse = $this->phoenixApiService->getUser($id);
            $user = $apiResponse['data'] ?? null;
            
            if (!$user) {
                throw $this->createNotFoundException('Użytkownik nie został znaleziony.');
            }
            
            return $this->render('admin/users/edit.html.twig', [
                'user' => $user,
            ]);
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to fetch user for editing in admin panel', [
                'user_id' => $id,
                'error' => $e->getMessage()
            ]);
            
            $this->addFlash('error', 'Nie można pobrać danych użytkownika do edycji: ' . $e->getMessage());
            
            return $this->redirectToRoute('admin_users_index');
        }
    }

    /**
     * Handle user update
     */
    #[Route('/{id}/update', name: 'update', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function update(int $id, Request $request): Response
    {
        $userData = [
            'first_name' => $request->request->get('first_name'),
            'last_name' => $request->request->get('last_name'),
            'birthdate' => $request->request->get('birthdate'),
            'gender' => $request->request->get('gender'),
        ];
        
        // Basic validation
        $errors = [];
        if (empty($userData['first_name'])) {
            $errors[] = 'Imię jest wymagane';
        }
        if (empty($userData['last_name'])) {
            $errors[] = 'Nazwisko jest wymagane';
        }
        if (empty($userData['birthdate'])) {
            $errors[] = 'Data urodzenia jest wymagana';
        }
        if (!in_array($userData['gender'], ['male', 'female'])) {
            $errors[] = 'Płeć musi być "male" lub "female"';
        }
        
        if (!empty($errors)) {
            foreach ($errors as $error) {
                $this->addFlash('error', $error);
            }
            return $this->render('admin/users/edit.html.twig', [
                'user' => array_merge(['id' => $id], $userData)
            ]);
        }
        
        try {
            $apiResponse = $this->phoenixApiService->updateUser($id, $userData);
            $user = $apiResponse['data'] ?? null;
            
            if ($user) {
                $this->addFlash('success', 'Użytkownik został pomyślnie zaktualizowany.');
                return $this->redirectToRoute('admin_users_show', ['id' => $id]);
            }
            
            throw new \Exception('Nie otrzymano danych zaktualizowanego użytkownika');
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to update user via admin panel', [
                'user_id' => $id,
                'user_data' => $userData,
                'error' => $e->getMessage()
            ]);
            
            $this->addFlash('error', 'Nie można zaktualizować użytkownika: ' . $e->getMessage());
            
            return $this->render('admin/users/edit.html.twig', [
                'user' => array_merge(['id' => $id], $userData)
            ]);
        }
    }

    /**
     * Handle user deletion
     */
    #[Route('/{id}/delete', name: 'delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(int $id): Response
    {
        try {
            $this->phoenixApiService->deleteUser($id);
            
            $this->addFlash('success', 'Użytkownik został pomyślnie usunięty.');
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to delete user via admin panel', [
                'user_id' => $id,
                'error' => $e->getMessage()
            ]);
            
            $this->addFlash('error', 'Nie można usunąć użytkownika: ' . $e->getMessage());
        }
        
        return $this->redirectToRoute('admin_users_index');
    }

    /**
     * API status check endpoint
     */
    #[Route('/api-status', name: 'api_status', methods: ['GET'])]
    public function apiStatus(): JsonResponse
    {
        $isAvailable = $this->phoenixApiService->isApiAvailable();
        
        return $this->json([
            'available' => $isAvailable,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }
}