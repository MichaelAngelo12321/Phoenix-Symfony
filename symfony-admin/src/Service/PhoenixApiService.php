<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Psr\Log\LoggerInterface;

/**
 * Service for communicating with Phoenix API
 * 
 * Handles all HTTP communication with the Phoenix backend API,
 * including user management operations and error handling.
 */
class PhoenixApiService
{
    private readonly string $apiBaseUrl;
    
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly LoggerInterface $logger,
        private readonly PhoenixAuthService $authService
    ) {
        $this->apiBaseUrl = $_ENV['PHOENIX_API_URL'] ?? 'http://localhost:4000/api';
    }
    


    /**
     * Get all users from Phoenix API with optional filtering and sorting
     * 
     * @param string $token JWT token for authentication
     * @param array<string, mixed> $params Query parameters for filtering and sorting
     * @return array<string, mixed>
     * @throws \Exception
     */
    public function getUsers(string $token, array $params = []): array
    {
        $endpoint = '/users';
        
        // Add query parameters if provided
        if (!empty($params)) {
            $endpoint .= '?' . http_build_query($params);
        }
        
        $result = $this->authService->makeAuthenticatedRequest('GET', $endpoint, $token);
        
        if (!$result['success']) {
            $this->logger->error('Failed to fetch users from Phoenix API', [
                'error' => $result['error']
            ]);
            throw new \Exception($result['error']);
        }
        
        if ($result['status_code'] !== 200) {
            $this->logger->error('Phoenix API returned non-200 status', [
                'status_code' => $result['status_code'],
                'response' => $result['data']
            ]);
            throw new \Exception("Phoenix API error: HTTP {$result['status_code']}");
        }
        
        $this->logger->info('Successfully fetched users from Phoenix API', [
            'count' => count($result['data']['data'] ?? []),
            'params' => $params
        ]);
        
        return $result['data'];
    }

    /**
     * Get a specific user by ID from Phoenix API
     * 
     * @param string $token JWT token for authentication
     * @param int $id
     * @return array<string, mixed>
     * @throws \Exception
     */
    public function getUser(string $token, int $id): array
    {
        $result = $this->authService->makeAuthenticatedRequest('GET', "/users/{$id}", $token);
        
        if (!$result['success']) {
            $this->logger->error('Failed to fetch user from Phoenix API', [
                'user_id' => $id,
                'error' => $result['error']
            ]);
            throw new \Exception($result['error']);
        }
        
        if ($result['status_code'] === 404) {
            throw new \Exception("User with ID {$id} not found");
        }
        
        if ($result['status_code'] !== 200) {
            $this->logger->error('Phoenix API returned non-200 status for user', [
                'user_id' => $id,
                'status_code' => $result['status_code'],
                'response' => $result['data']
            ]);
            throw new \Exception("Phoenix API error: HTTP {$result['status_code']}");
        }
        
        $this->logger->info('Successfully fetched user from Phoenix API', [
            'user_id' => $id
        ]);
        
        return $result['data'];
    }

    /**
     * Create a new user via Phoenix API
     * 
     * @param string $token JWT token for authentication
     * @param array<string, mixed> $userData
     * @return array<string, mixed>
     * @throws \Exception
     */
    public function createUser(string $token, array $userData): array
    {
        $result = $this->authService->makeAuthenticatedRequest('POST', '/users', $token, [
            'json' => ['user' => $userData]
        ]);
        
        if (!$result['success']) {
            $this->logger->error('Failed to create user via Phoenix API', [
                'error' => $result['error'],
                'user_data' => $userData
            ]);
            throw new \Exception($result['error']);
        }
        
        if ($result['status_code'] === 422) {
            $this->logger->warning('Validation error when creating user', [
                'errors' => $result['data']['errors'] ?? [],
                'user_data' => $userData
            ]);
            throw new \Exception('Validation error: ' . json_encode($result['data']['errors'] ?? []));
        }
        
        if ($result['status_code'] !== 201) {
            $this->logger->error('Phoenix API returned non-201 status for user creation', [
                'status_code' => $result['status_code'],
                'response' => $result['data'],
                'user_data' => $userData
            ]);
            throw new \Exception("Phoenix API error: HTTP {$result['status_code']}");
        }
        
        $this->logger->info('Successfully created user via Phoenix API', [
            'user_id' => $result['data']['data']['id'] ?? null
        ]);
        
        return $result['data'];
    }

    /**
     * Update an existing user via Phoenix API
     * 
     * @param string $token JWT token for authentication
     * @param int $id
     * @param array<string, mixed> $userData
     * @return array<string, mixed>
     * @throws \Exception
     */
    public function updateUser(string $token, int $id, array $userData): array
    {
        $result = $this->authService->makeAuthenticatedRequest('PUT', "/users/{$id}", $token, [
            'json' => ['user' => $userData]
        ]);
        
        if (!$result['success']) {
            $this->logger->error('Failed to update user via Phoenix API', [
                'user_id' => $id,
                'error' => $result['error'],
                'user_data' => $userData
            ]);
            throw new \Exception($result['error']);
        }
        
        if ($result['status_code'] === 404) {
            throw new \Exception("User with ID {$id} not found");
        }
        
        if ($result['status_code'] === 422) {
            $this->logger->warning('Validation error when updating user', [
                'user_id' => $id,
                'errors' => $result['data']['errors'] ?? [],
                'user_data' => $userData
            ]);
            throw new \Exception('Validation error: ' . json_encode($result['data']['errors'] ?? []));
        }
        
        if ($result['status_code'] !== 200) {
            $this->logger->error('Phoenix API returned non-200 status for user update', [
                'user_id' => $id,
                'status_code' => $result['status_code'],
                'response' => $result['data'],
                'user_data' => $userData
            ]);
            throw new \Exception("Phoenix API error: HTTP {$result['status_code']}");
        }
        
        $this->logger->info('Successfully updated user via Phoenix API', [
            'user_id' => $id
        ]);
        
        return $result['data'];
    }

    /**
     * Delete a user via Phoenix API
     * 
     * @param string $token JWT token for authentication
     * @param int $id
     * @return bool
     * @throws \Exception
     */
    public function deleteUser(string $token, int $id): bool
    {
        $result = $this->authService->makeAuthenticatedRequest('DELETE', "/users/{$id}", $token);
        
        if (!$result['success']) {
            $this->logger->error('Failed to delete user via Phoenix API', [
                'user_id' => $id,
                'error' => $result['error']
            ]);
            throw new \Exception($result['error']);
        }
        
        if ($result['status_code'] === 404) {
            throw new \Exception("User with ID {$id} not found");
        }
        
        if ($result['status_code'] !== 204) {
            $this->logger->error('Phoenix API returned non-204 status for user deletion', [
                'user_id' => $id,
                'status_code' => $result['status_code'],
                'response' => $result['data']
            ]);
            throw new \Exception("Phoenix API error: HTTP {$result['status_code']}");
        }
        
        $this->logger->info('Successfully deleted user via Phoenix API', [
            'user_id' => $id
        ]);
        
        return true;
    }

    /**
     * Check if Phoenix API is available
     * 
     * @return bool
     */
    public function isApiAvailable(): bool
    {
        try {
            $response = $this->httpClient->request('GET', $this->apiBaseUrl . '/users', [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ],
                'timeout' => 5,
            ]);

            return $response->getStatusCode() === 200;
            
        } catch (\Exception $e) {
            $this->logger->warning('Phoenix API availability check failed', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
}