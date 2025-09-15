<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Dto\FilterDto;
use App\Dto\UserDto;
use App\Dto\UserListResponseDto;
use App\Dto\UserRequestDto;
use App\Dto\UserResponseDto;
use App\Enum\UserMessage;
use App\Factory\ResponseFactoryInterface;
use App\Service\PhoenixApiServiceInterface;
use App\Service\UserService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;

final class UserServiceTest extends TestCase
{
    private UserService $userService;
    private PhoenixApiServiceInterface&MockObject $phoenixApiService;
    private LoggerInterface&MockObject $logger;
    private ResponseFactoryInterface&MockObject $responseFactory;

    protected function setUp(): void
    {
        $this->phoenixApiService = $this->createMock(PhoenixApiServiceInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->responseFactory = $this->createMock(ResponseFactoryInterface::class);
        
        $this->userService = new UserService(
            $this->phoenixApiService,
            $this->logger,
            $this->responseFactory
        );
    }

    public function testGetUsersApiError(): void
    {
        $token = 'test-token';
        $request = new Request();
        $filterDto = FilterDto::fromRequest($request);
        $exception = new \Exception('API Error');
        
        $expectedResponse = UserListResponseDto::failure(['API fetch users error']);

        $this->phoenixApiService
            ->method('getUsers')
            ->willThrowException($exception);

        $this->responseFactory
            ->method('createUserListFailureResponse')
            ->willReturn($expectedResponse);

        $this->responseFactory
            ->method('createUserListFailureResponse')
            ->willReturn($expectedResponse);

        $result = $this->userService->getUsers($token, $filterDto);

        $this->assertInstanceOf(UserListResponseDto::class, $result);
        $this->assertFalse($result->success);
    }

    public function testGetUserSuccess(): void
    {
        $token = 'test-token';
        $userId = 1;
        $apiResponse = ['data' => ['id' => 1, 'name' => 'John Doe', 'email' => 'john@example.com']];
        
        $expectedUser = new UserDto(1, 'John Doe', 'john@example.com', null, null);
        $expectedResponse = new UserResponseDto(
            success: true,
            user: $expectedUser,
            errors: [],
            message: null
        );

        $this->phoenixApiService
            ->method('getUser')
            ->with($token, $userId)
            ->willReturn($apiResponse);

        $this->responseFactory
            ->method('createUserSuccessResponse')
            ->willReturn($expectedResponse);

        $result = $this->userService->getUser($token, $userId);

        $this->assertInstanceOf(UserResponseDto::class, $result);
        $this->assertTrue($result->success);
        $this->assertNotNull($result->user);
    }

    public function testGetUserNotFound(): void
    {
        $token = 'test-token';
        $userId = 1;
        $apiResponse = ['data' => null];
        
        $expectedResponse = new UserResponseDto(
            success: false,
            user: null,
            errors: [UserMessage::FETCH_FAILED->value],
            message: null
        );

        $this->phoenixApiService
            ->method('getUser')
            ->with($token, $userId)
            ->willReturn($apiResponse);

        $this->responseFactory
            ->method('createUserFailureResponse')
            ->willReturn($expectedResponse);

        $result = $this->userService->getUser($token, $userId);

        $this->assertInstanceOf(UserResponseDto::class, $result);
        $this->assertFalse($result->success);
        $this->assertNull($result->user);
    }

    public function testDeleteUserSuccess(): void
    {
        $token = 'test-token';
        $userId = 1;

        $this->phoenixApiService
            ->method('deleteUser')
            ->with($token, $userId);

        $result = $this->userService->deleteUser($token, $userId);

        $this->assertInstanceOf(UserResponseDto::class, $result);
    }

    public function testDeleteUserApiError(): void
    {
        $token = 'test-token';
        $userId = 1;
        $exception = new \Exception('API Error');
        
        $expectedResponse = new UserResponseDto(
            success: false,
            user: null,
            errors: ['API delete error'],
            message: null
        );

        $this->phoenixApiService
            ->method('deleteUser')
            ->willThrowException($exception);

        $this->logger
            ->method('error')
            ->with(UserMessage::API_DELETE_ERROR->value);

        $this->responseFactory
            ->method('createUserFailureResponse')
            ->willReturn($expectedResponse);

        $result = $this->userService->deleteUser($token, $userId);

        $this->assertInstanceOf(UserResponseDto::class, $result);
        $this->assertFalse($result->success);
    }

    public function testImportUsersSuccess(): void
    {
        $token = 'test-token';
        $apiResponse = ['data' => [
            ['id' => 1, 'name' => 'John Doe', 'email' => 'john@example.com'],
            ['id' => 2, 'name' => 'Jane Doe', 'email' => 'jane@example.com']
        ]];
        
        $expectedUsers = [
            new UserDto(1, 'John Doe', 'john@example.com', null, null),
            new UserDto(2, 'Jane Doe', 'jane@example.com', null, null)
        ];
        $expectedResponse = new UserListResponseDto(
            success: true,
            users: $expectedUsers,
            errors: [],
            currentFilters: [],
            sortBy: 'id',
            sortOrder: 'asc'
        );

        $this->phoenixApiService
            ->method('importUsers')
            ->with($token)
            ->willReturn($apiResponse);

        $this->responseFactory
            ->method('createUserListSuccessResponse')
            ->willReturn($expectedResponse);

        $result = $this->userService->importUsers($token);

        $this->assertInstanceOf(UserListResponseDto::class, $result);
        $this->assertTrue($result->success);
        $this->assertCount(2, $result->users);
    }
}