<?php

declare(strict_types=1);

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * User business logic service
 *
 * Handles user-related business operations
 */
final class UserService implements UserServiceInterface
{
    public function __construct(
        private readonly PhoenixApiServiceInterface $phoenixApiService,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * Get users with filtering and sorting
     *
     * @param string $token JWT token for API authentication
     * @param Request $request HTTP request with filter parameters
     *
     * @return array{users: array, api_available: bool, current_filters: array, sort_by: string, sort_order: string, errors: array}
     */
    public function getUsers(string $token, Request $request): array
    {
        try {
            // Extract filters
            $filters = $this->extractFilters($request);

            // Get sorting parameters
            $sortBy = $request->query->get('sort_by', 'id');
            $sortOrder = $request->query->get('sort_order', 'asc');

            // Remove empty filters
            $filters = array_filter($filters, static fn ($value) => $value !== null && $value !== '');

            // Add sorting to filters
            if ($sortBy) {
                $filters['sort_by'] = $sortBy;
                $filters['sort_order'] = $sortOrder;
            }

            $apiResponse = $this->phoenixApiService->getUsers($token, $filters);
            $users = $apiResponse['data'] ?? [];

            return [
                'users' => $users,
                'api_available' => true,
                'current_filters' => $request->query->all(),
                'sort_by' => $sortBy,
                'sort_order' => $sortOrder,
                'errors' => [],
            ];
        } catch (\Exception $e) {
            $this->logger->error('Failed to fetch users for admin panel', [
                'error' => $e->getMessage(),
            ]);

            return [
                'users' => [],
                'api_available' => false,
                'current_filters' => [],
                'sort_by' => 'id',
                'sort_order' => 'asc',
                'errors' => ['Nie można pobrać listy użytkowników: ' . $e->getMessage()],
            ];
        }
    }

    /**
     * Get single user by ID
     *
     * @param string $token JWT token for API authentication
     * @param int $id User ID
     *
     * @return array{user: array|null, api_available: bool, errors: array}
     */
    public function getUser(string $token, int $id): array
    {
        try {
            $apiResponse = $this->phoenixApiService->getUser($token, $id);
            $user = $apiResponse['data'] ?? null;

            return [
                'user' => $user,
                'api_available' => true,
                'errors' => [],
            ];
        } catch (\Exception $e) {
            $this->logger->error('Failed to fetch user from Phoenix API', [
                'user_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return [
                'user' => null,
                'api_available' => false,
                'errors' => ['Nie można pobrać danych użytkownika: ' . $e->getMessage()],
            ];
        }
    }

    /**
     * Create new user
     *
     * @param string $token JWT token for API authentication
     * @param array<string, mixed> $userData User data to create
     *
     * @return array{success: bool, user: array|null, errors: array}
     */
    public function createUser(string $token, array $userData): array
    {
        // Convert DateTime to string for API compatibility
        if (isset($userData['birthdate']) && $userData['birthdate'] instanceof \DateTime) {
            $userData['birthdate'] = $userData['birthdate']->format('Y-m-d');
        }

        // Convert first_name and last_name to uppercase
        if (isset($userData['first_name'])) {
            $userData['first_name'] = strtoupper($userData['first_name']);
        }
        if (isset($userData['last_name'])) {
            $userData['last_name'] = strtoupper($userData['last_name']);
        }

        try {
            $apiResponse = $this->phoenixApiService->createUser($token, $userData);

            // PhoenixApiService returns data directly from API on success
            return [
                'success' => true,
                'user' => $apiResponse['data'] ?? null,
                'errors' => [],
            ];
        } catch (\Exception $e) {
            $this->logger->error('Failed to create user via Phoenix API', [
                'user_data' => $userData,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'user' => null,
                'errors' => ['Nie można utworzyć użytkownika: ' . $e->getMessage()],
            ];
        }
    }

    /**
     * Update existing user
     *
     * @param string $token JWT token for API authentication
     * @param int $id User ID
     * @param array<string, mixed> $userData User data to update
     *
     * @return array{success: bool, user: array|null, errors: array}
     */
    public function updateUser(string $token, int $id, array $userData): array
    {
        // Convert DateTime to string for API
        if (isset($userData['birthdate']) && $userData['birthdate'] instanceof \DateTime) {
            $userData['birthdate'] = $userData['birthdate']->format('Y-m-d');
        }

        if (isset($userData['first_name'])) {
            $userData['first_name'] = strtoupper($userData['first_name']);
        }
        if (isset($userData['last_name'])) {
            $userData['last_name'] = strtoupper($userData['last_name']);
        }

        try {
            $this->phoenixApiService->updateUser($token, $id, $userData);

            return [
                'success' => true,
                'user' => $apiResponse['data'] ?? null,
                'errors' => [],
            ];
        } catch (\Exception $e) {
            $this->logger->error('Failed to update user via Phoenix API', [
                'user_id' => $id,
                'user_data' => $userData,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'user' => null,
                'errors' => ['Nie można zaktualizować użytkownika: ' . $e->getMessage()],
            ];
        }
    }

    /**
     * Delete user
     *
     * @param string $token JWT token for API authentication
     * @param int $id User ID
     *
     * @return array{success: bool, errors: array}
     */
    public function deleteUser(string $token, int $id): array
    {
        try {
            $this->phoenixApiService->deleteUser($token, $id);

            return [
                'success' => true,
                'errors' => [],
            ];
        } catch (\Exception $e) {
            $this->logger->error('Failed to delete user via Phoenix API', [
                'user_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'errors' => ['Nie można usunąć użytkownika: ' . $e->getMessage()],
            ];
        }
    }

    public function importUsers(string $token): array
    {
        try {
            $result = $this->phoenixApiService->importUsers($token);

            return [
                'success' => true,
                'count' => $result['count'] ?? 0,
                'errors' => [],
            ];
        } catch (\Exception $e) {
            $this->logger->error('Failed to import users', [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'count' => 0,
                'errors' => ['Nie można zaimportować użytkowników: ' . $e->getMessage()],
            ];
        }
    }

    public function checkApiStatus(): array
    {
        try {
            $isAvailable = $this->phoenixApiService->isApiAvailable();

            return [
                'available' => $isAvailable,
                'errors' => [],
            ];
        } catch (\Exception $e) {
            $this->logger->error('Failed to check API status', [
                'error' => $e->getMessage(),
            ]);

            return [
                'available' => false,
                'errors' => ['Nie można sprawdzić statusu API: ' . $e->getMessage()],
            ];
        }
    }

    /**
     * Extract filter parameters from request
     *
     * @return array<string, mixed>
     */
    private function extractFilters(Request $request): array
    {
        return [
            'first_name' => $request->query->get('first_name'),
            'last_name' => $request->query->get('last_name'),
            'gender' => $request->query->get('gender'),
            'birthdate_from' => $request->query->get('birthdate_from'),
            'birthdate_to' => $request->query->get('birthdate_to'),
        ];
    }
}
