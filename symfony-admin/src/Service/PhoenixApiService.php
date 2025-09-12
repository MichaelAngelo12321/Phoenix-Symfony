<?php

declare(strict_types=1);

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final readonly class PhoenixApiService implements PhoenixApiServiceInterface
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private LoggerInterface $logger,
        private PhoenixAuthServiceInterface $authService,
        #[Autowire('%env(PHOENIX_API_URL)%')]
        private string $apiBaseUrl,
    ) {
    }

    /**
     * @return array<string, mixed>
     *
     * @throws \Exception
     */
    public function getUsers(string $token, array $params = []): array
    {
        $endpoint = '/users';

        if (count($params) > 0) {
            $endpoint .= '?' . http_build_query($params);
        }

        $result = $this->authService->makeAuthenticatedRequest('GET', $endpoint, $token);

        if (! $result['success']) {
            $this->logger->error('Failed to fetch users from Phoenix API', [
                'error' => $result['error'],
            ]);
            throw new \Exception($result['error']);
        }

        if ($result['status_code'] !== 200) {
            $this->logger->error('Phoenix API returned non-200 status', [
                'status_code' => $result['status_code'],
                'response' => $result['data'],
            ]);
            throw new \Exception("Phoenix API error: HTTP {$result['status_code']}");
        }

        $this->logger->info('Successfully fetched users from Phoenix API', [
            'count' => count($result['data']['data'] ?? []),
            'params' => $params,
        ]);

        return $result['data'];
    }

    /**
     * @return array<string, mixed>
     *
     * @throws \Exception
     */
    public function getUser(string $token, int $id): array
    {
        $result = $this->authService->makeAuthenticatedRequest('GET', "/users/{$id}", $token);

        if (! $result['success']) {
            $this->logger->error('Failed to fetch user from Phoenix API', [
                'user_id' => $id,
                'error' => $result['error'],
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
                'response' => $result['data'],
            ]);
            throw new \Exception("Phoenix API error: HTTP {$result['status_code']}");
        }

        $this->logger->info('Successfully fetched user from Phoenix API', [
            'user_id' => $id,
        ]);

        return $result['data'];
    }

    /**
     * @return array{success: bool, data: array|null, error: string|null, status_code: int}
     *
     * @throws \Exception
     */
    public function createUser(string $token, array $userData): array
    {
        $result = $this->authService->makeAuthenticatedRequest('POST', '/users', $token, [
            'json' => ['user' => $userData],
        ]);

        if (! $result['success']) {
            $this->logger->error('Failed to create user via Phoenix API', [
                'error' => $result['error'],
                'user_data' => $userData,
            ]);
            throw new \Exception($result['error']);
        }

        if ($result['status_code'] === 422) {
            $this->logger->warning('Validation error when creating user', [
                'errors' => $result['data']['errors'] ?? [],
                'user_data' => $userData,
            ]);
            throw new \Exception('Validation error: ' . json_encode($result['data']['errors'] ?? []));
        }

        if ($result['status_code'] !== 201) {
            $this->logger->error('Phoenix API returned non-201 status for user creation', [
                'status_code' => $result['status_code'],
                'response' => $result['data'],
                'user_data' => $userData,
            ]);
            throw new \Exception("Phoenix API error: HTTP {$result['status_code']}");
        }

        $this->logger->info('Successfully created user via Phoenix API', [
            'user_id' => $result['data']['data']['id'] ?? null,
        ]);

        return $result['data'];
    }

    /**
     * @return array<string, mixed>
     *
     * @throws \Exception
     */
    public function updateUser(string $token, int $id, array $userData): array
    {
        $result = $this->authService->makeAuthenticatedRequest('PUT', "/users/{$id}", $token, [
            'json' => ['user' => $userData],
        ]);

        if (! $result['success']) {
            $this->logger->error('Failed to update user via Phoenix API', [
                'user_id' => $id,
                'error' => $result['error'],
                'user_data' => $userData,
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
                'user_data' => $userData,
            ]);
            throw new \Exception('Validation error: ' . json_encode($result['data']['errors'] ?? []));
        }

        if ($result['status_code'] !== 200) {
            $this->logger->error('Phoenix API returned non-200 status for user update', [
                'user_id' => $id,
                'status_code' => $result['status_code'],
                'response' => $result['data'],
                'user_data' => $userData,
            ]);
            throw new \Exception("Phoenix API error: HTTP {$result['status_code']}");
        }

        $this->logger->info('Successfully updated user via Phoenix API', [
            'user_id' => $id,
        ]);

        return $result['data'];
    }

    /**
     * @throws \Exception
     */
    public function deleteUser(string $token, int $id): bool
    {
        $result = $this->authService->makeAuthenticatedRequest('DELETE', "/users/{$id}", $token);

        if (! $result['success']) {
            $this->logger->error('Failed to delete user via Phoenix API', [
                'user_id' => $id,
                'error' => $result['error'],
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
                'response' => $result['data'],
            ]);
            throw new \Exception("Phoenix API error: HTTP {$result['status_code']}");
        }

        $this->logger->info('Successfully deleted user via Phoenix API', [
            'user_id' => $id,
        ]);

        return true;
    }

    /**
     * @return array<string, mixed>
     *
     * @throws \Exception
     */
    public function importUsers(string $token): array
    {
        $result = $this->authService->makeAuthenticatedRequest('POST', '/import', $token);

        if (! isset($result['success']) || ! $result['success']) {
            throw new \Exception($result['error'] ?? 'Import failed');
        }

        return $result;
    }

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
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }
}
