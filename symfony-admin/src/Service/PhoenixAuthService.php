<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\AuthenticationResultDto;
use App\Dto\TokenVerificationResultDto;
use App\Enum\HttpStatus;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
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

    public function login(string $email, string $password): AuthenticationResultDto
    {
        $payload = ['email' => $email, 'password' => $password];
        $response = $this->makeApiRequest('POST', '/auth/login', $payload);

        if (! $response['success']) {
            return AuthenticationResultDto::failure($response['error']);
        }

        return $this->processLoginResponse($response['data']);
    }

    public function verifyToken(string $token): TokenVerificationResultDto
    {
        $payload = ['token' => $token];
        $response = $this->makeApiRequest('POST', '/auth/verify', $payload);

        if (! $response['success']) {
            return TokenVerificationResultDto::failure($response['error']);
        }

        return $this->processVerifyResponse($response['data']);
    }

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

            if ($statusCode === HttpStatus::NO_CONTENT->value || $response->getContent(false) === '') {
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
                'success' => $statusCode === HttpStatus::OK->value,
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

    private function processLoginResponse(array $data): AuthenticationResultDto
    {
        if (isset($data['success']) && $data['success']) {
            return AuthenticationResultDto::success(
                $data['token'],
                $data['admin']
            );
        }

        return AuthenticationResultDto::failure(
            $data['error'] ?? 'Authentication failed'
        );
    }

    private function processVerifyResponse(array $data): TokenVerificationResultDto
    {
        if (isset($data['success']) && $data['success']) {
            if ($data['valid']) {
                return TokenVerificationResultDto::valid($data['admin'] ?? []);
            }
            return TokenVerificationResultDto::invalid($data['error'] ?? 'Token is invalid');
        }

        return TokenVerificationResultDto::failure(
            $data['error'] ?? 'Token verification failed'
        );
    }
}
