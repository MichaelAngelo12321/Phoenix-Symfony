<?php

namespace App\Controller;

use App\Service\PhoenixApiService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
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
     * Get JWT token from session or redirect to login
     */
    private function getTokenOrRedirect(SessionInterface $session): string|Response
    {
        $token = $session->get('admin_token');
        
        if (!$token) {
            $this->addFlash('error', 'Musisz się zalogować, aby uzyskać dostęp do tej strony.');
            return $this->redirectToRoute('app_login');
        }
        
        return $token;
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
        
        try {
            // Get filter parameters
            $filters = [
                'first_name' => $request->query->get('first_name'),
                'last_name' => $request->query->get('last_name'),
                'gender' => $request->query->get('gender'),
                'birthdate_from' => $request->query->get('birthdate_from'),
                'birthdate_to' => $request->query->get('birthdate_to'),
            ];
            
            // Get sorting parameters
            $sortBy = $request->query->get('sort_by', 'id');
            $sortOrder = $request->query->get('sort_order', 'asc');
            
            // Remove empty filters
            $filters = array_filter($filters, fn($value) => !empty($value));
            
            // Add sorting to filters
            if ($sortBy) {
                $filters['sort_by'] = $sortBy;
                $filters['sort_order'] = $sortOrder;
            }
            
            $apiResponse = $this->phoenixApiService->getUsers($token, $filters);
            $users = $apiResponse['data'] ?? [];
            
            return $this->render('admin/users/index.html.twig', [
                'users' => $users,
                'api_available' => true,
                'current_filters' => $request->query->all(),
                'sort_by' => $sortBy,
                'sort_order' => $sortOrder,
            ]);
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to fetch users for admin panel', [
                'error' => $e->getMessage()
            ]);
            
            $this->addFlash('error', 'Nie można pobrać listy użytkowników: ' . $e->getMessage());
            
            return $this->render('admin/users/index.html.twig', [
                'users' => [],
                'api_available' => false,
                'current_filters' => [],
                'sort_by' => 'id',
                'sort_order' => 'asc',
            ]);
        }
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
        
        try {
            $apiResponse = $this->phoenixApiService->getUser($token, $id);
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
    public function new(SessionInterface $session): Response
    {
        $token = $this->getTokenOrRedirect($session);
        if ($token instanceof Response) {
            return $token;
        }
        
        return $this->render('admin/users/new.html.twig');
    }

    /**
     * Handle user creation
     */
    #[Route('/create', name: 'create', methods: ['POST'])]
    public function create(Request $request, SessionInterface $session): Response
    {
        $token = $this->getTokenOrRedirect($session);
        if ($token instanceof Response) {
            return $token;
        }
        
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
            $apiResponse = $this->phoenixApiService->createUser($token, $userData);
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
    public function edit(int $id, SessionInterface $session): Response
    {
        $token = $this->getTokenOrRedirect($session);
        if ($token instanceof Response) {
            return $token;
        }
        
        try {
            $apiResponse = $this->phoenixApiService->getUser($token, $id);
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
    public function update(int $id, Request $request, SessionInterface $session): Response
    {
        $token = $this->getTokenOrRedirect($session);
        if ($token instanceof Response) {
            return $token;
        }
        
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
            $apiResponse = $this->phoenixApiService->updateUser($token, $id, $userData);
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
    public function delete(int $id, SessionInterface $session): Response
    {
        $token = $this->getTokenOrRedirect($session);
        if ($token instanceof Response) {
            return $token;
        }
        
        try {
            $this->phoenixApiService->deleteUser($token, $id);
            
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
     * Import users from external API
     */
    #[Route('/import', name: 'import', methods: ['POST'])]
    public function import(SessionInterface $session): Response
    {
        $token = $this->getTokenOrRedirect($session);
        if ($token instanceof Response) {
            return $token;
        }
        
        try {
            $result = $this->phoenixApiService->importUsers($token);
            
            $this->addFlash('success', sprintf(
                'Import zakończony pomyślnie! Zaimportowano %d użytkowników.',
                $result['count'] ?? 0
            ));
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to import users via admin panel', [
                'error' => $e->getMessage()
            ]);
            
            $this->addFlash('error', 'Nie można zaimportować użytkowników: ' . $e->getMessage());
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