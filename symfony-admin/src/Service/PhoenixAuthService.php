<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final readonly class PhoenixAuthService implements PhoenixAuthServiceInterface
{
    public function __construct(
        private HttpClientInterface $httpClient,
        #[Autowire('%env(PHOENIX_API_URL)%')]
        private string $phoenixApiUrl,
    ) {
    }

    /**
     * Authenticate admin with Phoenix API
     */
    public function login(string $email, string $password): array
    {
        $payload = ['email' => $email, 'password' => $password];
        $response = $this->makeApiRequest('POST', '/auth/login', $payload);

        if (! $response['success']) {
            return $response;
        }

        return $this->processLoginResponse($response['data']);
    }

    /**
     * Verify JWT token with Phoenix API
     */
    public function verifyToken(string $token): array
    {
        $payload = ['token' => $token];
        $response = $this->makeApiRequest('POST', '/auth/verify', $payload);

        if (! $response['success']) {
            return array_merge($response, ['valid' => false]);
        }

        return $this->processVerifyResponse($response['data']);
    }

    /**
     * Make authenticated request to Phoenix API
     */
    public function makeAuthenticatedRequest(string $method, string $endpoint, string $token, array $options = []): array
    {
        try {
            $defaultOptions = [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ],
            ];

            $options = array_merge_recursive($defaultOptions, $options);

            $response = $this->httpClient->request($method, $this->phoenixApiUrl . $endpoint, $options);

            $statusCode = $response->getStatusCode();

            // Handle responses without content (like DELETE operations)
            if ($statusCode === 204 || $response->getContent(false) === '') {
                return [
                    'success' => true,
                    'status_code' => $statusCode,
                    'data' => [],
                ];
            }

            return [
                'success' => true,
                'status_code' => $statusCode,
                'data' => $response->toArray(false),
            ];
        } catch (TransportExceptionInterface $e) {
            return [
                'success' => false,
                'error' => 'Connection to Phoenix API failed: ' . $e->getMessage(),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Unexpected error: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Make API request with error handling
     */
    private function makeApiRequest(string $method, string $endpoint, array $payload): array
    {
        try {
            $response = $this->httpClient->request($method, $this->phoenixApiUrl . $endpoint, [
                'json' => $payload,
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ],
            ]);

            $statusCode = $response->getStatusCode();
            $data = $response->toArray(false);

            return [
                'success' => $statusCode === Response::HTTP_OK,
                'status_code' => $statusCode,
                'data' => $data,
            ];
        } catch (TransportExceptionInterface $e) {
            return [
                'success' => false,
                'error' => 'Connection to Phoenix API failed: ' . $e->getMessage(),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Unexpected error: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Process login response data
     */
    private function processLoginResponse(array $data): array
    {
        if (isset($data['success']) && $data['success']) {
            return [
                'success' => true,
                'token' => $data['token'],
                'admin' => $data['admin'],
            ];
        }

        return [
            'success' => false,
            'error' => $data['error'] ?? 'Authentication failed',
        ];
    }

    /**
     * Process token verification response data
     */
    private function processVerifyResponse(array $data): array
    {
        if (isset($data['success']) && $data['success']) {
            return [
                'success' => true,
                'valid' => $data['valid'],
                'admin' => $data['admin'] ?? null,
            ];
        }

        return [
            'success' => false,
            'valid' => false,
            'error' => $data['error'] ?? 'Token verification failed',
        ];
    }
}
