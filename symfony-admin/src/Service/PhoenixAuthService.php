<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Component\HttpFoundation\Response;

class PhoenixAuthService
{
    private HttpClientInterface $httpClient;
    private string $phoenixApiUrl;

    public function __construct(HttpClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
        $this->phoenixApiUrl = $_ENV['PHOENIX_API_URL'] ?? 'http://localhost:4000/api';
    }

    /**
     * Authenticate admin with Phoenix API
     */
    public function login(string $email, string $password): array
    {
        try {
            $response = $this->httpClient->request('POST', $this->phoenixApiUrl . '/auth/login', [
                'json' => [
                    'email' => $email,
                    'password' => $password
                ],
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json'
                ]
            ]);

            $statusCode = $response->getStatusCode();
            $data = $response->toArray(false);

            if ($statusCode === Response::HTTP_OK && isset($data['success']) && $data['success']) {
                return [
                    'success' => true,
                    'token' => $data['token'],
                    'admin' => $data['admin']
                ];
            }

            return [
                'success' => false,
                'error' => $data['error'] ?? 'Authentication failed'
            ];

        } catch (TransportExceptionInterface $e) {
            return [
                'success' => false,
                'error' => 'Connection to Phoenix API failed: ' . $e->getMessage()
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Unexpected error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Verify JWT token with Phoenix API
     */
    public function verifyToken(string $token): array
    {
        try {
            $response = $this->httpClient->request('POST', $this->phoenixApiUrl . '/auth/verify', [
                'json' => [
                    'token' => $token
                ],
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json'
                ]
            ]);

            $statusCode = $response->getStatusCode();
            $data = $response->toArray(false);

            if ($statusCode === Response::HTTP_OK && isset($data['success']) && $data['success']) {
                return [
                    'success' => true,
                    'valid' => $data['valid'],
                    'admin' => $data['admin'] ?? null
                ];
            }

            return [
                'success' => false,
                'valid' => false,
                'error' => $data['error'] ?? 'Token verification failed'
            ];

        } catch (TransportExceptionInterface $e) {
            return [
                'success' => false,
                'valid' => false,
                'error' => 'Connection to Phoenix API failed: ' . $e->getMessage()
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'valid' => false,
                'error' => 'Unexpected error: ' . $e->getMessage()
            ];
        }
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
                    'Accept' => 'application/json'
                ]
            ];

            $options = array_merge_recursive($defaultOptions, $options);

            $response = $this->httpClient->request($method, $this->phoenixApiUrl . $endpoint, $options);
            
            $statusCode = $response->getStatusCode();
            
            // Handle responses without content (like DELETE operations)
            if ($statusCode === 204 || $response->getContent(false) === '') {
                return [
                    'success' => true,
                    'status_code' => $statusCode,
                    'data' => []
                ];
            }

            return [
                'success' => true,
                'status_code' => $statusCode,
                'data' => $response->toArray(false)
            ];

        } catch (TransportExceptionInterface $e) {
            return [
                'success' => false,
                'error' => 'Connection to Phoenix API failed: ' . $e->getMessage()
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Unexpected error: ' . $e->getMessage()
            ];
        }
    }
}