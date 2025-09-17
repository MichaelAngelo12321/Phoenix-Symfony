<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Service\CacheablePhoenixApiService;
use App\Service\PhoenixApiServiceInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

final class CacheablePhoenixApiServiceTest extends TestCase
{
    private CacheablePhoenixApiService $cacheableService;
    private PhoenixApiServiceInterface|MockObject $phoenixApiService;
    private CacheInterface|MockObject $cache;
    private LoggerInterface|MockObject $logger;

    protected function setUp(): void
    {
        $this->phoenixApiService = $this->createMock(PhoenixApiServiceInterface::class);
        $this->cache = $this->createMock(CacheInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->cacheableService = new CacheablePhoenixApiService(
            $this->phoenixApiService,
            $this->cache,
            $this->logger
        );
    }

    public function testGetUsersReturnsCachedDataWhenAvailable(): void
    {
        // Arrange
        $token = 'test-token';
        $params = ['limit' => 10];
        $expectedResponse = [
            'data' => [
                ['id' => 1, 'first_name' => 'John', 'last_name' => 'Doe']
            ],
            'meta' => ['total' => 1]
        ];

        $this->cache->expects($this->once())
            ->method('get')
            ->willReturnCallback(function ($key, $callback) use ($expectedResponse) {
                return $expectedResponse;
            });

        // Act
        $result = $this->cacheableService->getUsers($token, $params);

        // Assert
        $this->assertEquals($expectedResponse, $result);
    }

    public function testGetUsersFetchesFromApiWhenCacheEmpty(): void
    {
        // Arrange
        $token = 'test-token';
        $params = [];
        $apiResponse = [
            'data' => [
                ['id' => 1, 'first_name' => 'Jane', 'last_name' => 'Smith']
            ]
        ];

        $item = $this->createMock(ItemInterface::class);
        $item->expects($this->once())->method('expiresAfter')->with(300);

        $this->cache->expects($this->once())
            ->method('get')
            ->willReturnCallback(function ($key, $callback) use ($item, $apiResponse) {
                return $callback($item);
            });

        $this->phoenixApiService->expects($this->once())
            ->method('getUsers')
            ->with($token, $params)
            ->willReturn($apiResponse);

        $this->logger->expects($this->once())
            ->method('info')
            ->with('Users fetched from API and cached');

        // Act
        $result = $this->cacheableService->getUsers($token, $params);

        // Assert
        $this->assertEquals($apiResponse, $result);
    }

    public function testGetUsersUsesFallbackWhenApiThrowsException(): void
    {
        // Arrange
        $token = 'test-token';
        $filters = [];
        $sortBy = 'first_name';
        $sortOrder = 'asc';

        $this->cache->expects($this->once())
            ->method('get')
            ->willReturn(null);

        $this->phoenixApiService->expects($this->once())
            ->method('getUsers')
            ->with($token, $filters, $sortBy, $sortOrder)
            ->willThrowException(new \Exception('API Error'));

        // Act
        $result = $this->cacheableService->getUsers($token, $filters, $sortBy, $sortOrder);

        // Assert
        $this->assertEquals(['data' => []], $result);
    }

    public function testGetUserReturnsCachedDataWhenAvailable(): void
    {
        // Arrange
        $token = 'test-token';
        $userId = 1;
        $expectedUser = ['id' => 1, 'first_name' => 'John', 'last_name' => 'Doe'];

        $this->cache->expects($this->once())
            ->method('get')
            ->willReturnCallback(function ($key, $callback) use ($expectedUser) {
                return $expectedUser;
            });

        // Act
        $result = $this->cacheableService->getUser($token, $userId);

        // Assert
        $this->assertEquals($expectedUser, $result);
    }

    public function testGetUserFetchesFromApiWhenCacheEmpty(): void
    {
        // Arrange
        $token = 'test-token';
        $userId = 1;
        $apiResponse = ['id' => 1, 'first_name' => 'Jane', 'last_name' => 'Smith'];

        $item = $this->createMock(ItemInterface::class);
        $item->expects($this->once())->method('expiresAfter')->with(300);

        $this->cache->expects($this->once())
            ->method('get')
            ->willReturnCallback(function ($key, $callback) use ($item, $apiResponse) {
                return $callback($item);
            });

        $this->phoenixApiService->expects($this->once())
            ->method('getUser')
            ->with($token, $userId)
            ->willReturn($apiResponse);

        $this->logger->expects($this->once())
            ->method('info')
            ->with('User fetched from API and cached');

        // Act
        $result = $this->cacheableService->getUser($token, $userId);

        // Assert
        $this->assertEquals($apiResponse, $result);
    }

    public function testCreateUserInvalidatesCache(): void
    {
        // Arrange
        $token = 'test-token';
        $userData = ['first_name' => 'New', 'last_name' => 'User'];
        $createdUser = ['id' => 2, 'first_name' => 'New', 'last_name' => 'User'];

        $this->phoenixApiService->expects($this->once())
            ->method('createUser')
            ->with($token, $userData)
            ->willReturn($createdUser);

        $this->cache->expects($this->once())
            ->method('delete')
            ->with('phoenix_api_users')
            ->willReturn(true);

        // Act
        $result = $this->cacheableService->createUser($token, $userData);

        // Assert
        $this->assertEquals($createdUser, $result);
    }

    public function testUpdateUserInvalidatesCache(): void
    {
        // Arrange
        $token = 'test-token';
        $userId = 1;
        $userData = ['first_name' => 'Updated'];
        $updatedUser = ['id' => 1, 'first_name' => 'Updated', 'last_name' => 'User'];

        $this->phoenixApiService->expects($this->once())
            ->method('updateUser')
            ->with($token, $userId, $userData)
            ->willReturn($updatedUser);

        $this->cache->expects($this->exactly(2))
            ->method('delete')
            ->willReturnOnConsecutiveCalls(true, true);

        // Act
        $result = $this->cacheableService->updateUser($token, $userId, $userData);

        // Assert
        $this->assertEquals($updatedUser, $result);
    }

    public function testDeleteUserInvalidatesCache(): void
    {
        // Arrange
        $token = 'test-token';
        $userId = 1;

        $this->phoenixApiService->expects($this->once())
            ->method('deleteUser')
            ->with($token, $userId)
            ->willReturn(true);

        $this->cache->expects($this->exactly(2))
            ->method('delete')
            ->willReturnOnConsecutiveCalls(true, true);

        // Act
        $result = $this->cacheableService->deleteUser($token, $userId);

        // Assert
        $this->assertTrue($result);
    }

    public function testImportUsersInvalidatesCache(): void
    {
        // Arrange
        $token = 'test-token';
        $importResult = ['imported' => 2, 'failed' => 0];

        $this->phoenixApiService->expects($this->once())
            ->method('importUsers')
            ->with($token)
            ->willReturn($importResult);

        $this->cache->expects($this->once())
            ->method('delete')
            ->with('phoenix_api_users')
            ->willReturn(true);

        // Act
        $result = $this->cacheableService->importUsers($token);

        // Assert
        $this->assertEquals($importResult, $result);
    }

    public function testIsApiAvailablePassesThroughToOriginalService(): void
    {
        // Arrange
        $this->phoenixApiService->expects($this->once())
            ->method('isApiAvailable')
            ->willReturn(true);

        // Act
        $result = $this->cacheableService->isApiAvailable();

        // Assert
        $this->assertTrue($result);
    }
}