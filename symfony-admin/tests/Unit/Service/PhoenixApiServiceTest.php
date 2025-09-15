<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Enum\HttpStatus;
use App\Exception\PhoenixApiException;
use App\Service\PhoenixApiService;
use App\Service\PhoenixAuthServiceInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

final class PhoenixApiServiceTest extends TestCase
{
    private PhoenixApiService $phoenixApiService;
    private HttpClientInterface&MockObject $httpClient;
    private LoggerInterface&MockObject $logger;
    private PhoenixAuthServiceInterface&MockObject $authService;
    private string $apiBaseUrl = 'http://localhost:4000/api';

    protected function setUp(): void
    {
        $this->httpClient = $this->createMock(HttpClientInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->authService = $this->createMock(PhoenixAuthServiceInterface::class);

        $this->phoenixApiService = new PhoenixApiService(
            $this->httpClient,
            $this->logger,
            $this->authService,
            $this->apiBaseUrl
        );
    }

    public function testGetUsersSuccess(): void
    {
        $token = 'test-token';
        $params = ['page' => 1, 'limit' => 10];
        $expectedResult = [
            'data' => [
                ['id' => 1, 'first_name' => 'John', 'last_name' => 'Doe'],
                ['id' => 2, 'first_name' => 'Jane', 'last_name' => 'Smith'],
            ],
        ];

        $authResponse = [
            'success' => true,
            'status_code' => HttpStatus::OK->value,
            'data' => $expectedResult,
        ];

        $this->authService
            ->expects($this->once())
            ->method('makeAuthenticatedRequest')
            ->with('GET', '/users?page=1&limit=10', $token)
            ->willReturn($authResponse);

        $this->logger
            ->expects($this->once())
            ->method('info')
            ->with('Successfully fetched users from Phoenix API', [
                'count' => 2,
                'params' => $params,
            ]);

        $result = $this->phoenixApiService->getUsers($token, $params);

        $this->assertEquals($expectedResult, $result);
    }

    public function testGetUsersWithoutParams(): void
    {
        $token = 'test-token';
        $expectedResult = ['data' => []];

        $authResponse = [
            'success' => true,
            'status_code' => HttpStatus::OK->value,
            'data' => $expectedResult,
        ];

        $this->authService
            ->expects($this->once())
            ->method('makeAuthenticatedRequest')
            ->with('GET', '/users', $token)
            ->willReturn($authResponse);

        $result = $this->phoenixApiService->getUsers($token);

        $this->assertEquals($expectedResult, $result);
    }

    public function testGetUserSuccess(): void
    {
        $token = 'test-token';
        $userId = 1;
        $expectedResult = [
            'data' => ['id' => 1, 'first_name' => 'John', 'last_name' => 'Doe'],
        ];

        $authResponse = [
            'success' => true,
            'status_code' => HttpStatus::OK->value,
            'data' => $expectedResult,
        ];

        $this->authService
            ->expects($this->once())
            ->method('makeAuthenticatedRequest')
            ->with('GET', '/users/1', $token)
            ->willReturn($authResponse);

        $this->logger
            ->expects($this->once())
            ->method('info')
            ->with('Successfully fetched user from Phoenix API', ['user_id' => 1]);

        $result = $this->phoenixApiService->getUser($token, $userId);

        $this->assertEquals($expectedResult, $result);
    }

    public function testGetUserNotFound(): void
    {
        $token = 'test-token';
        $userId = 999;

        $authResponse = [
            'success' => true,
            'status_code' => HttpStatus::NOT_FOUND->value,
            'data' => null,
        ];

        $this->authService
            ->expects($this->once())
            ->method('makeAuthenticatedRequest')
            ->with('GET', '/users/999', $token)
            ->willReturn($authResponse);

        $this->expectException(PhoenixApiException::class);
        $this->expectExceptionMessage('User with ID 999 not found');

        $this->phoenixApiService->getUser($token, $userId);
    }

    public function testCreateUserSuccess(): void
    {
        $token = 'test-token';
        $userData = ['first_name' => 'John', 'last_name' => 'Doe'];
        $expectedResult = [
            'data' => ['id' => 1, 'first_name' => 'John', 'last_name' => 'Doe'],
        ];

        $authResponse = [
            'success' => true,
            'status_code' => HttpStatus::CREATED->value,
            'data' => $expectedResult,
        ];

        $this->authService
            ->expects($this->once())
            ->method('makeAuthenticatedRequest')
            ->with('POST', '/users', $token, ['json' => ['user' => $userData]])
            ->willReturn($authResponse);

        $this->logger
            ->expects($this->once())
            ->method('info')
            ->with('Successfully created user via Phoenix API', ['user_id' => 1]);

        $result = $this->phoenixApiService->createUser($token, $userData);

        $this->assertEquals($expectedResult, $result);
    }

    public function testCreateUserValidationError(): void
    {
        $token = 'test-token';
        $userData = ['first_name' => ''];
        $errors = ['first_name' => ['cannot be blank']];

        $authResponse = [
            'success' => true,
            'status_code' => HttpStatus::UNPROCESSABLE_ENTITY->value,
            'data' => ['errors' => $errors],
        ];

        $this->authService
            ->expects($this->once())
            ->method('makeAuthenticatedRequest')
            ->willReturn($authResponse);

        $this->logger
            ->expects($this->once())
            ->method('warning')
            ->with('Validation error when creating user', [
                'errors' => $errors,
                'user_data' => $userData,
            ]);

        $this->expectException(PhoenixApiException::class);

        $this->phoenixApiService->createUser($token, $userData);
    }

    public function testUpdateUserSuccess(): void
    {
        $token = 'test-token';
        $userId = 1;
        $userData = ['first_name' => 'John Updated'];
        $expectedResult = [
            'data' => ['id' => 1, 'first_name' => 'John Updated', 'last_name' => 'Doe'],
        ];

        $authResponse = [
            'success' => true,
            'status_code' => HttpStatus::OK->value,
            'data' => $expectedResult,
        ];

        $this->authService
            ->expects($this->once())
            ->method('makeAuthenticatedRequest')
            ->with('PUT', '/users/1', $token, ['json' => ['user' => $userData]])
            ->willReturn($authResponse);

        $this->logger
            ->expects($this->once())
            ->method('info')
            ->with('Successfully updated user via Phoenix API', ['user_id' => 1]);

        $result = $this->phoenixApiService->updateUser($token, $userId, $userData);

        $this->assertEquals($expectedResult, $result);
    }

    public function testUpdateUserNotFound(): void
    {
        $token = 'test-token';
        $userId = 999;
        $userData = ['first_name' => 'John'];

        $authResponse = [
            'success' => true,
            'status_code' => HttpStatus::NOT_FOUND->value,
            'data' => null,
        ];

        $this->authService
            ->expects($this->once())
            ->method('makeAuthenticatedRequest')
            ->willReturn($authResponse);

        $this->expectException(PhoenixApiException::class);
        $this->expectExceptionMessage('User with ID 999 not found');

        $this->phoenixApiService->updateUser($token, $userId, $userData);
    }

    public function testDeleteUserSuccess(): void
    {
        $token = 'test-token';
        $userId = 1;

        $authResponse = [
            'success' => true,
            'status_code' => HttpStatus::NO_CONTENT->value,
            'data' => null,
        ];

        $this->authService
            ->expects($this->once())
            ->method('makeAuthenticatedRequest')
            ->with('DELETE', '/users/1', $token)
            ->willReturn($authResponse);

        $this->logger
            ->expects($this->once())
            ->method('info')
            ->with('Successfully deleted user via Phoenix API', ['user_id' => 1]);

        $result = $this->phoenixApiService->deleteUser($token, $userId);

        $this->assertTrue($result);
    }

    public function testDeleteUserNotFound(): void
    {
        $token = 'test-token';
        $userId = 999;

        $authResponse = [
            'success' => true,
            'status_code' => HttpStatus::NOT_FOUND->value,
            'data' => null,
        ];

        $this->authService
            ->expects($this->once())
            ->method('makeAuthenticatedRequest')
            ->willReturn($authResponse);

        $this->expectException(PhoenixApiException::class);
        $this->expectExceptionMessage('User with ID 999 not found');

        $this->phoenixApiService->deleteUser($token, $userId);
    }

    public function testImportUsersSuccess(): void
    {
        $token = 'test-token';
        $expectedResult = [
            'success' => true,
            'data' => ['imported' => 5],
        ];

        $this->authService
            ->expects($this->once())
            ->method('makeAuthenticatedRequest')
            ->with('POST', '/import', $token)
            ->willReturn($expectedResult);

        $result = $this->phoenixApiService->importUsers($token);

        $this->assertEquals($expectedResult, $result);
    }

    public function testImportUsersFailure(): void
    {
        $token = 'test-token';
        $authResponse = [
            'success' => false,
            'error' => 'Import failed',
        ];

        $this->authService
            ->expects($this->once())
            ->method('makeAuthenticatedRequest')
            ->willReturn($authResponse);

        $this->expectException(PhoenixApiException::class);
        $this->expectExceptionMessage('Import failed');

        $this->phoenixApiService->importUsers($token);
    }

    public function testIsApiAvailableSuccess(): void
    {
        $response = $this->createMock(ResponseInterface::class);
        $response
            ->expects($this->once())
            ->method('getStatusCode')
            ->willReturn(HttpStatus::OK->value);

        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->with('GET', 'http://localhost:4000/api/users', [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ],
                'timeout' => 5,
            ])
            ->willReturn($response);

        $result = $this->phoenixApiService->isApiAvailable();

        $this->assertTrue($result);
    }

    public function testIsApiAvailableFailure(): void
    {
        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->willThrowException(new \Exception('Connection failed'));

        $this->logger
            ->expects($this->once())
            ->method('warning')
            ->with('Phoenix API availability check failed', [
                'error' => 'Connection failed',
            ]);

        $result = $this->phoenixApiService->isApiAvailable();

        $this->assertFalse($result);
    }

    public function testValidateApiResponseConnectionFailed(): void
    {
        $token = 'test-token';
        $authResponse = [
            'success' => false,
            'error' => 'Connection failed',
        ];

        $this->authService
            ->expects($this->once())
            ->method('makeAuthenticatedRequest')
            ->willReturn($authResponse);

        $this->logger
            ->expects($this->once())
            ->method('error')
            ->with('Failed to fetch users', ['error' => 'Connection failed']);

        $this->expectException(PhoenixApiException::class);

        $this->phoenixApiService->getUsers($token);
    }
}
