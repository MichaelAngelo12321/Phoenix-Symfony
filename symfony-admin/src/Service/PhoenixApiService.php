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
        private readonly LoggerInterface $logger
    ) {
        $this->apiBaseUrl = $_ENV['PHOENIX_API_URL'] ?? 'http://localhost:4000/api';
    }
    


    /**
     * Get all users from Phoenix API
     * 
     * @return array<string, mixed>
     * @throws \Exception
     */
    public function getUsers(): array
    {
        try {
            $response = $this->httpClient->request('GET', $this->apiBaseUrl . '/users', [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ],
                'timeout' => 10,
            ]);

            $statusCode = $response->getStatusCode();
            
            if ($statusCode !== 200) {
                $this->logger->error('Phoenix API returned non-200 status', [
                    'status_code' => $statusCode,
                    'response' => $response->getContent(false)
                ]);
                throw new \Exception("Phoenix API error: HTTP {$statusCode}");
            }

            $data = $response->toArray();
            
            $this->logger->info('Successfully fetched users from Phoenix API', [
                'count' => count($data['data'] ?? [])
            ]);
            
            return $data;
            
        } catch (TransportExceptionInterface $e) {
            $this->logger->error('Transport error when calling Phoenix API', [
                'error' => $e->getMessage()
            ]);
            throw new \Exception('Failed to connect to Phoenix API: ' . $e->getMessage(), 0, $e);
        } catch (ClientExceptionInterface|ServerExceptionInterface|RedirectionExceptionInterface $e) {
            $this->logger->error('HTTP error when calling Phoenix API', [
                'error' => $e->getMessage()
            ]);
            throw new \Exception('Phoenix API HTTP error: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Get a specific user by ID from Phoenix API
     * 
     * @param int $id
     * @return array<string, mixed>
     * @throws \Exception
     */
    public function getUser(int $id): array
    {
        try {
            $response = $this->httpClient->request('GET', $this->apiBaseUrl . "/users/{$id}", [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ],
                'timeout' => 10,
            ]);

            $statusCode = $response->getStatusCode();
            
            if ($statusCode === 404) {
                throw new \Exception("User with ID {$id} not found");
            }
            
            if ($statusCode !== 200) {
                $this->logger->error('Phoenix API returned non-200 status for user', [
                    'user_id' => $id,
                    'status_code' => $statusCode,
                    'response' => $response->getContent(false)
                ]);
                throw new \Exception("Phoenix API error: HTTP {$statusCode}");
            }

            $data = $response->toArray();
            
            $this->logger->info('Successfully fetched user from Phoenix API', [
                'user_id' => $id
            ]);
            
            return $data;
            
        } catch (TransportExceptionInterface $e) {
            $this->logger->error('Transport error when calling Phoenix API for user', [
                'user_id' => $id,
                'error' => $e->getMessage()
            ]);
            throw new \Exception('Failed to connect to Phoenix API: ' . $e->getMessage(), 0, $e);
        } catch (ClientExceptionInterface|ServerExceptionInterface|RedirectionExceptionInterface $e) {
            $this->logger->error('HTTP error when calling Phoenix API for user', [
                'user_id' => $id,
                'error' => $e->getMessage()
            ]);
            throw new \Exception('Phoenix API HTTP error: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Create a new user via Phoenix API
     * 
     * @param array<string, mixed> $userData
     * @return array<string, mixed>
     * @throws \Exception
     */
    public function createUser(array $userData): array
    {
        try {
            $response = $this->httpClient->request('POST', $this->apiBaseUrl . '/users', [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ],
                'json' => ['user' => $userData],
                'timeout' => 10,
            ]);

            $statusCode = $response->getStatusCode();
            
            if ($statusCode === 422) {
                $errorData = $response->toArray(false);
                $this->logger->warning('Validation error when creating user', [
                    'errors' => $errorData['errors'] ?? [],
                    'user_data' => $userData
                ]);
                throw new \Exception('Validation error: ' . json_encode($errorData['errors'] ?? []));
            }
            
            if ($statusCode !== 201) {
                $this->logger->error('Phoenix API returned non-201 status for user creation', [
                    'status_code' => $statusCode,
                    'response' => $response->getContent(false),
                    'user_data' => $userData
                ]);
                throw new \Exception("Phoenix API error: HTTP {$statusCode}");
            }

            $data = $response->toArray();
            
            $this->logger->info('Successfully created user via Phoenix API', [
                'user_id' => $data['data']['id'] ?? null
            ]);
            
            return $data;
            
        } catch (TransportExceptionInterface $e) {
            $this->logger->error('Transport error when creating user via Phoenix API', [
                'error' => $e->getMessage(),
                'user_data' => $userData
            ]);
            throw new \Exception('Failed to connect to Phoenix API: ' . $e->getMessage(), 0, $e);
        } catch (ClientExceptionInterface|ServerExceptionInterface|RedirectionExceptionInterface $e) {
            $this->logger->error('HTTP error when creating user via Phoenix API', [
                'error' => $e->getMessage(),
                'user_data' => $userData
            ]);
            throw new \Exception('Phoenix API HTTP error: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Update an existing user via Phoenix API
     * 
     * @param int $id
     * @param array<string, mixed> $userData
     * @return array<string, mixed>
     * @throws \Exception
     */
    public function updateUser(int $id, array $userData): array
    {
        try {
            $response = $this->httpClient->request('PUT', $this->apiBaseUrl . "/users/{$id}", [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ],
                'json' => ['user' => $userData],
                'timeout' => 10,
            ]);

            $statusCode = $response->getStatusCode();
            
            if ($statusCode === 404) {
                throw new \Exception("User with ID {$id} not found");
            }
            
            if ($statusCode === 422) {
                $errorData = $response->toArray(false);
                $this->logger->warning('Validation error when updating user', [
                    'user_id' => $id,
                    'errors' => $errorData['errors'] ?? [],
                    'user_data' => $userData
                ]);
                throw new \Exception('Validation error: ' . json_encode($errorData['errors'] ?? []));
            }
            
            if ($statusCode !== 200) {
                $this->logger->error('Phoenix API returned non-200 status for user update', [
                    'user_id' => $id,
                    'status_code' => $statusCode,
                    'response' => $response->getContent(false),
                    'user_data' => $userData
                ]);
                throw new \Exception("Phoenix API error: HTTP {$statusCode}");
            }

            $data = $response->toArray();
            
            $this->logger->info('Successfully updated user via Phoenix API', [
                'user_id' => $id
            ]);
            
            return $data;
            
        } catch (TransportExceptionInterface $e) {
            $this->logger->error('Transport error when updating user via Phoenix API', [
                'user_id' => $id,
                'error' => $e->getMessage(),
                'user_data' => $userData
            ]);
            throw new \Exception('Failed to connect to Phoenix API: ' . $e->getMessage(), 0, $e);
        } catch (ClientExceptionInterface|ServerExceptionInterface|RedirectionExceptionInterface $e) {
            $this->logger->error('HTTP error when updating user via Phoenix API', [
                'user_id' => $id,
                'error' => $e->getMessage(),
                'user_data' => $userData
            ]);
            throw new \Exception('Phoenix API HTTP error: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Delete a user via Phoenix API
     * 
     * @param int $id
     * @return bool
     * @throws \Exception
     */
    public function deleteUser(int $id): bool
    {
        try {
            $response = $this->httpClient->request('DELETE', $this->apiBaseUrl . "/users/{$id}", [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ],
                'timeout' => 10,
            ]);

            $statusCode = $response->getStatusCode();
            
            if ($statusCode === 404) {
                throw new \Exception("User with ID {$id} not found");
            }
            
            if ($statusCode !== 204) {
                $this->logger->error('Phoenix API returned non-204 status for user deletion', [
                    'user_id' => $id,
                    'status_code' => $statusCode,
                    'response' => $response->getContent(false)
                ]);
                throw new \Exception("Phoenix API error: HTTP {$statusCode}");
            }
            
            $this->logger->info('Successfully deleted user via Phoenix API', [
                'user_id' => $id
            ]);
            
            return true;
            
        } catch (TransportExceptionInterface $e) {
            $this->logger->error('Transport error when deleting user via Phoenix API', [
                'user_id' => $id,
                'error' => $e->getMessage()
            ]);
            throw new \Exception('Failed to connect to Phoenix API: ' . $e->getMessage(), 0, $e);
        } catch (ClientExceptionInterface|ServerExceptionInterface|RedirectionExceptionInterface $e) {
            $this->logger->error('HTTP error when deleting user via Phoenix API', [
                'user_id' => $id,
                'error' => $e->getMessage()
            ]);
            throw new \Exception('Phoenix API HTTP error: ' . $e->getMessage(), 0, $e);
        }
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