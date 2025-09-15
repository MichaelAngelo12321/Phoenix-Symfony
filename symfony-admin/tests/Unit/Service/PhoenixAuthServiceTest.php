<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Dto\AuthenticationResultDto;
use App\Dto\TokenVerificationResultDto;
use App\Enum\HttpStatus;
use App\Service\PhoenixAuthService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

final class PhoenixAuthServiceTest extends TestCase
{
    private PhoenixAuthService $phoenixAuthService;
    private HttpClientInterface&MockObject $httpClient;
    private string $phoenixApiUrl = 'http://localhost:4000/api';

    protected function setUp(): void
    {
        $this->httpClient = $this->createMock(HttpClientInterface::class);
        
        $this->phoenixAuthService = new PhoenixAuthService(
            $this->httpClient,
            $this->phoenixApiUrl
        );
    }

    public function testLoginSuccess(): void
    {
        $email = 'admin@example.com';
        $password = 'password123';
        $token = 'jwt-token-123';
        $adminData = ['id' => 1, 'email' => 'admin@example.com'];
        
        $responseData = [
            'success' => true,
            'token' => $token,
            'admin' => $adminData
        ];

        $response = $this->createMock(ResponseInterface::class);
        $response
            ->expects($this->once())
            ->method('getStatusCode')
            ->willReturn(HttpStatus::OK->value);
        $response
            ->expects($this->once())
            ->method('toArray')
            ->with(false)
            ->willReturn($responseData);

        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->with('POST', 'http://localhost:4000/api/auth/login', [
                'json' => ['email' => $email, 'password' => $password],
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ],
            ])
            ->willReturn($response);

        $result = $this->phoenixAuthService->login($email, $password);

        $this->assertInstanceOf(AuthenticationResultDto::class, $result);
        $this->assertTrue($result->success);
        $this->assertSame($token, $result->token);
        $this->assertSame($adminData, $result->admin);
    }

    public function testLoginFailureInvalidCredentials(): void
    {
        $email = 'admin@example.com';
        $password = 'wrong-password';
        
        $responseData = [
            'error' => 'Invalid credentials'
        ];

        $response = $this->createMock(ResponseInterface::class);
        $response
            ->expects($this->once())
            ->method('getStatusCode')
            ->willReturn(401);
        $response
            ->expects($this->once())
            ->method('toArray')
            ->with(false)
            ->willReturn($responseData);

        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->willReturn($response);

        $result = $this->phoenixAuthService->login($email, $password);

        $this->assertInstanceOf(AuthenticationResultDto::class, $result);
        $this->assertFalse($result->success);
        $this->assertSame('Authentication failed', $result->error);
    }

    public function testLoginConnectionError(): void
    {
        $email = 'admin@example.com';
        $password = 'password123';
        
        $exception = new \Exception('Connection refused');

        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->willThrowException($exception);

        $result = $this->phoenixAuthService->login($email, $password);

        $this->assertInstanceOf(AuthenticationResultDto::class, $result);
        $this->assertFalse($result->success);
        $this->assertStringContainsString('Unexpected error', $result->error);
    }

    public function testVerifyTokenValid(): void
    {
        $token = 'valid-jwt-token';
        $adminData = ['id' => 1, 'email' => 'admin@example.com'];
        
        $responseData = [
            'success' => true,
            'valid' => true,
            'admin' => $adminData
        ];

        $response = $this->createMock(ResponseInterface::class);
        $response
            ->expects($this->once())
            ->method('getStatusCode')
            ->willReturn(HttpStatus::OK->value);
        $response
            ->expects($this->once())
            ->method('toArray')
            ->with(false)
            ->willReturn($responseData);

        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->with('POST', 'http://localhost:4000/api/auth/verify', [
                'json' => ['token' => $token],
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ],
            ])
            ->willReturn($response);

        $result = $this->phoenixAuthService->verifyToken($token);

        $this->assertInstanceOf(TokenVerificationResultDto::class, $result);
        $this->assertTrue($result->success);
        $this->assertTrue($result->valid);
        $this->assertSame($adminData, $result->admin);
    }

    public function testVerifyTokenInvalid(): void
    {
        $token = 'invalid-jwt-token';
        
        $responseData = [
            'success' => true,
            'valid' => false,
            'error' => 'Token expired'
        ];

        $response = $this->createMock(ResponseInterface::class);
        $response
            ->expects($this->once())
            ->method('getStatusCode')
            ->willReturn(HttpStatus::OK->value);
        $response
            ->expects($this->once())
            ->method('toArray')
            ->with(false)
            ->willReturn($responseData);

        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->willReturn($response);

        $result = $this->phoenixAuthService->verifyToken($token);

        $this->assertInstanceOf(TokenVerificationResultDto::class, $result);
        $this->assertTrue($result->success);
        $this->assertFalse($result->valid);
        $this->assertSame('Token expired', $result->error);
    }

    public function testVerifyTokenConnectionError(): void
    {
        $token = 'some-token';
        
        $exception = new \Exception('Network error');

        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->willThrowException($exception);

        $result = $this->phoenixAuthService->verifyToken($token);

        $this->assertInstanceOf(TokenVerificationResultDto::class, $result);
        $this->assertFalse($result->success);
        $this->assertStringContainsString('Unexpected error', $result->error);
    }

    public function testMakeAuthenticatedRequestSuccess(): void
    {
        $method = 'GET';
        $endpoint = '/users';
        $token = 'valid-token';
        $responseData = ['data' => ['users' => []]];

        $response = $this->createMock(ResponseInterface::class);
        $response
            ->expects($this->once())
            ->method('getStatusCode')
            ->willReturn(HttpStatus::OK->value);
        $response
            ->expects($this->once())
            ->method('getContent')
            ->with(false)
            ->willReturn('{"data":{"users":[]}}');
        $response
            ->expects($this->once())
            ->method('toArray')
            ->with(false)
            ->willReturn($responseData);

        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->with($method, 'http://localhost:4000/api/users', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ],
            ])
            ->willReturn($response);

        $result = $this->phoenixAuthService->makeAuthenticatedRequest($method, $endpoint, $token);

        $this->assertTrue($result['success']);
        $this->assertSame(HttpStatus::OK->value, $result['status_code']);
        $this->assertSame($responseData, $result['data']);
    }

    public function testMakeAuthenticatedRequestWithOptions(): void
    {
        $method = 'POST';
        $endpoint = '/users';
        $token = 'valid-token';
        $options = ['json' => ['name' => 'John Doe']];
        $responseData = ['data' => ['id' => 1]];

        $response = $this->createMock(ResponseInterface::class);
        $response
            ->expects($this->once())
            ->method('getStatusCode')
            ->willReturn(HttpStatus::CREATED->value);
        $response
            ->expects($this->once())
            ->method('getContent')
            ->with(false)
            ->willReturn('{"data":{"id":1}}');
        $response
            ->expects($this->once())
            ->method('toArray')
            ->with(false)
            ->willReturn($responseData);

        $expectedOptions = [
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ],
            'json' => ['name' => 'John Doe'],
        ];

        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->with($method, 'http://localhost:4000/api/users', $expectedOptions)
            ->willReturn($response);

        $result = $this->phoenixAuthService->makeAuthenticatedRequest($method, $endpoint, $token, $options);

        $this->assertTrue($result['success']);
        $this->assertSame(HttpStatus::CREATED->value, $result['status_code']);
        $this->assertSame($responseData, $result['data']);
    }

    public function testMakeAuthenticatedRequestConnectionError(): void
    {
        $method = 'GET';
        $endpoint = '/users';
        $token = 'valid-token';
        
        $exception = new \Exception('Connection timeout');

        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->willThrowException($exception);

        $result = $this->phoenixAuthService->makeAuthenticatedRequest($method, $endpoint, $token);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Unexpected error', $result['error']);
    }

    public function testMakeAuthenticatedRequestUnexpectedError(): void
    {
        $method = 'GET';
        $endpoint = '/users';
        $token = 'valid-token';
        
        $exception = new \RuntimeException('Unexpected error occurred');

        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->willThrowException($exception);

        $result = $this->phoenixAuthService->makeAuthenticatedRequest($method, $endpoint, $token);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Unexpected error', $result['error']);
    }
}