<?php

declare(strict_types=1);

namespace App\Service;

use App\Enum\HttpStatus;
use App\Exception\PhoenixApiException;
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

    public function getUsers(string $token, array $params = []): array
    {
        $endpoint = '/users';

        if (count($params) > 0) {
            $endpoint .= '?' . http_build_query($params);
        }

        $result = $this->authService->makeAuthenticatedRequest('GET', $endpoint, $token);
        $this->validateApiResponse($result, HttpStatus::OK, 'Failed to fetch users');

        $this->logger->info('Successfully fetched users from Phoenix API', [
            'count' => count($result['data']['data'] ?? []),
            'params' => $params,
        ]);

        return $result['data'];
    }

    public function getUser(string $token, int $id): array
    {
        $result = $this->authService->makeAuthenticatedRequest('GET', "/users/{$id}", $token);

        if ($result['status_code'] === HttpStatus::NOT_FOUND->value) {
            throw PhoenixApiException::notFound('User', $id);
        }

        $this->validateApiResponse($result, HttpStatus::OK, "Failed to fetch user {$id}");

        $this->logger->info('Successfully fetched user from Phoenix API', [
            'user_id' => $id,
        ]);

        return $result['data'];
    }

    public function createUser(string $token, array $userData): array
    {
        $result = $this->authService->makeAuthenticatedRequest('POST', '/users', $token, [
            'json' => ['user' => $userData],
        ]);

        if ($result['status_code'] === HttpStatus::UNPROCESSABLE_ENTITY->value) {
            $this->logger->warning('Validation error when creating user', [
                'errors' => $result['data']['errors'] ?? [],
                'user_data' => $userData,
            ]);
            throw PhoenixApiException::validationError($result['data']['errors'] ?? []);
        }

        $this->validateApiResponse($result, HttpStatus::CREATED, 'Failed to create user', $userData);

        $this->logger->info('Successfully created user via Phoenix API', [
            'user_id' => $result['data']['data']['id'] ?? null,
        ]);

        return $result['data'];
    }

    public function updateUser(string $token, int $id, array $userData): array
    {
        $result = $this->authService->makeAuthenticatedRequest('PUT', "/users/{$id}", $token, [
            'json' => ['user' => $userData],
        ]);

        if ($result['status_code'] === HttpStatus::NOT_FOUND->value) {
            throw PhoenixApiException::notFound('User', $id);
        }

        if ($result['status_code'] === HttpStatus::UNPROCESSABLE_ENTITY->value) {
            $this->logger->warning('Validation error when updating user', [
                'user_id' => $id,
                'errors' => $result['data']['errors'] ?? [],
                'user_data' => $userData,
            ]);
            throw PhoenixApiException::validationError($result['data']['errors'] ?? []);
        }

        $this->validateApiResponse($result, HttpStatus::OK, "Failed to update user {$id}", $userData);

        $this->logger->info('Successfully updated user via Phoenix API', [
            'user_id' => $id,
        ]);

        return $result['data'];
    }

    public function deleteUser(string $token, int $id): bool
    {
        $result = $this->authService->makeAuthenticatedRequest('DELETE', "/users/{$id}", $token);

        if ($result['status_code'] === HttpStatus::NOT_FOUND->value) {
            throw PhoenixApiException::notFound('User', $id);
        }

        $this->validateApiResponse($result, HttpStatus::NO_CONTENT, "Failed to delete user {$id}");

        $this->logger->info('Successfully deleted user via Phoenix API', [
            'user_id' => $id,
        ]);

        return true;
    }

    public function importUsers(string $token): array
    {
        $result = $this->authService->makeAuthenticatedRequest('POST', '/import', $token);

        if (! isset($result['success']) || ! $result['success']) {
            throw new PhoenixApiException($result['error'] ?? 'Import failed');
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

            return $response->getStatusCode() === HttpStatus::OK->value;
        } catch (\Exception $e) {
            $this->logger->warning('Phoenix API availability check failed', [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    private function validateApiResponse(
        array $result,
        HttpStatus $expectedStatus,
        string $errorContext,
        ?array $userData = null
    ): void {
        if (! $result['success']) {
            $logContext = ['error' => $result['error']];
            if ($userData) {
                $logContext['user_data'] = $userData;
            }
            $this->logger->error($errorContext, $logContext);
            throw PhoenixApiException::connectionFailed($result['error']);
        }

        if ($result['status_code'] !== $expectedStatus->value) {
            $logContext = [
                'status_code' => $result['status_code'],
                'response' => $result['data'],
            ];
            if ($userData) {
                $logContext['user_data'] = $userData;
            }
            $this->logger->error($errorContext . ' - unexpected status code', $logContext);
            throw PhoenixApiException::fromResponse($result['status_code'], $result['data'], $errorContext);
        }
    }
}
