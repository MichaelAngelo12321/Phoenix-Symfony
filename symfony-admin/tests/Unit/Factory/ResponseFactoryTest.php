<?php

declare(strict_types=1);

namespace App\Tests\Unit\Factory;

use App\Dto\UserDto;
use App\Dto\UserListResponseDto;
use App\Dto\UserResponseDto;
use App\Enum\GenderEnum;
use App\Factory\ResponseFactory;
use PHPUnit\Framework\TestCase;

final class ResponseFactoryTest extends TestCase
{
    private ResponseFactory $responseFactory;
    private UserDto $sampleUser;

    protected function setUp(): void
    {
        $this->responseFactory = new ResponseFactory();
        $this->sampleUser = new UserDto(
            id: 1,
            firstName: 'Jan',
            lastName: 'Kowalski',
            birthdate: new \DateTime('1990-05-15'),
            gender: GenderEnum::MALE
        );
    }

    public function testCreateUserSuccessResponseWithMessage(): void
    {
        $message = 'Użytkownik został utworzony pomyślnie';

        $response = $this->responseFactory->createUserSuccessResponse($this->sampleUser, $message);

        $this->assertInstanceOf(UserResponseDto::class, $response);
        $this->assertTrue($response->success);
        $this->assertSame($this->sampleUser, $response->user);
        $this->assertSame($message, $response->message);
        $this->assertEmpty($response->errors);
        $this->assertTrue($response->apiAvailable);
    }

    public function testCreateUserSuccessResponseWithoutMessage(): void
    {
        $response = $this->responseFactory->createUserSuccessResponse($this->sampleUser);

        $this->assertInstanceOf(UserResponseDto::class, $response);
        $this->assertTrue($response->success);
        $this->assertSame($this->sampleUser, $response->user);
        $this->assertNull($response->message);
        $this->assertEmpty($response->errors);
        $this->assertTrue($response->apiAvailable);
    }

    public function testCreateUserFailureResponseWithApiAvailable(): void
    {
        $errors = ['Błąd walidacji', 'Nieprawidłowe dane'];

        $response = $this->responseFactory->createUserFailureResponse($errors, true);

        $this->assertInstanceOf(UserResponseDto::class, $response);
        $this->assertFalse($response->success);
        $this->assertNull($response->user);
        $this->assertSame($errors, $response->errors);
        $this->assertTrue($response->apiAvailable);
        $this->assertNull($response->message);
    }

    public function testCreateUserFailureResponseWithApiUnavailable(): void
    {
        $errors = ['API jest niedostępne'];

        $response = $this->responseFactory->createUserFailureResponse($errors, false);

        $this->assertInstanceOf(UserResponseDto::class, $response);
        $this->assertFalse($response->success);
        $this->assertNull($response->user);
        $this->assertSame($errors, $response->errors);
        $this->assertFalse($response->apiAvailable);
        $this->assertNull($response->message);
    }

    public function testCreateUserFailureResponseWithDefaultApiAvailable(): void
    {
        $errors = ['Błąd serwera'];

        $response = $this->responseFactory->createUserFailureResponse($errors);

        $this->assertInstanceOf(UserResponseDto::class, $response);
        $this->assertFalse($response->success);
        $this->assertTrue($response->apiAvailable);
        $this->assertSame($errors, $response->errors);
    }

    public function testCreateUserListSuccessResponseWithAllParameters(): void
    {
        $users = [$this->sampleUser];
        $currentFilters = ['firstName' => 'Jan', 'gender' => 'male'];
        $sortBy = 'lastName';
        $sortOrder = 'desc';

        $response = $this->responseFactory->createUserListSuccessResponse(
            $users,
            $currentFilters,
            $sortBy,
            $sortOrder
        );

        $this->assertInstanceOf(UserListResponseDto::class, $response);
        $this->assertTrue($response->success);
        $this->assertSame($users, $response->users);
        $this->assertSame($currentFilters, $response->currentFilters);
        $this->assertSame($sortBy, $response->sortBy);
        $this->assertSame($sortOrder, $response->sortOrder);
        $this->assertEmpty($response->errors);
        $this->assertTrue($response->apiAvailable);
    }

    public function testCreateUserListSuccessResponseWithDefaultParameters(): void
    {
        $users = [$this->sampleUser];

        $response = $this->responseFactory->createUserListSuccessResponse($users);

        $this->assertInstanceOf(UserListResponseDto::class, $response);
        $this->assertTrue($response->success);
        $this->assertSame($users, $response->users);
        $this->assertEmpty($response->currentFilters);
        $this->assertSame('id', $response->sortBy);
        $this->assertSame('asc', $response->sortOrder);
        $this->assertEmpty($response->errors);
        $this->assertTrue($response->apiAvailable);
    }

    public function testCreateUserListSuccessResponseWithEmptyUsers(): void
    {
        $users = [];
        $currentFilters = ['firstName' => 'Nieistniejący'];

        $response = $this->responseFactory->createUserListSuccessResponse($users, $currentFilters);

        $this->assertInstanceOf(UserListResponseDto::class, $response);
        $this->assertTrue($response->success);
        $this->assertEmpty($response->users);
        $this->assertSame($currentFilters, $response->currentFilters);
    }

    public function testCreateUserListFailureResponseWithApiAvailable(): void
    {
        $errors = ['Błąd podczas pobierania użytkowników', 'Timeout połączenia'];

        $response = $this->responseFactory->createUserListFailureResponse($errors, true);

        $this->assertInstanceOf(UserListResponseDto::class, $response);
        $this->assertFalse($response->success);
        $this->assertEmpty($response->users);
        $this->assertSame($errors, $response->errors);
        $this->assertTrue($response->apiAvailable);
        $this->assertEmpty($response->currentFilters);
        $this->assertSame('id', $response->sortBy);
        $this->assertSame('asc', $response->sortOrder);
    }

    public function testCreateUserListFailureResponseWithApiUnavailable(): void
    {
        $errors = ['API Phoenix jest niedostępne'];

        $response = $this->responseFactory->createUserListFailureResponse($errors, false);

        $this->assertInstanceOf(UserListResponseDto::class, $response);
        $this->assertFalse($response->success);
        $this->assertEmpty($response->users);
        $this->assertSame($errors, $response->errors);
        $this->assertFalse($response->apiAvailable);
    }

    public function testCreateUserListFailureResponseWithDefaultApiAvailable(): void
    {
        $errors = ['Błąd serwera'];

        $response = $this->responseFactory->createUserListFailureResponse($errors);

        $this->assertInstanceOf(UserListResponseDto::class, $response);
        $this->assertFalse($response->success);
        $this->assertTrue($response->apiAvailable);
        $this->assertSame($errors, $response->errors);
    }

    public function testResponseFactoryIsReadonly(): void
    {
        $reflection = new \ReflectionClass(ResponseFactory::class);

        $this->assertTrue($reflection->isReadOnly());
    }

    public function testCreateUserListSuccessResponseWithMultipleUsers(): void
    {
        $user2 = new UserDto(
            id: 2,
            firstName: 'Anna',
            lastName: 'Nowak',
            birthdate: new \DateTime('1985-08-20'),
            gender: GenderEnum::FEMALE
        );

        $users = [$this->sampleUser, $user2];
        $currentFilters = ['gender' => 'all'];

        $response = $this->responseFactory->createUserListSuccessResponse($users, $currentFilters);

        $this->assertInstanceOf(UserListResponseDto::class, $response);
        $this->assertTrue($response->success);
        $this->assertCount(2, $response->users);
        $this->assertSame($this->sampleUser, $response->users[0]);
        $this->assertSame($user2, $response->users[1]);
        $this->assertSame($currentFilters, $response->currentFilters);
    }

    public function testCreateUserListSuccessResponseWithComplexFilters(): void
    {
        $users = [$this->sampleUser];
        $currentFilters = [
            'firstName' => 'Jan',
            'lastName' => 'Kowalski',
            'gender' => 'male',
            'birthdateFrom' => '1990-01-01',
            'birthdateTo' => '1990-12-31',
        ];

        $response = $this->responseFactory->createUserListSuccessResponse(
            $users,
            $currentFilters,
            'birthdate',
            'desc'
        );

        $this->assertInstanceOf(UserListResponseDto::class, $response);
        $this->assertTrue($response->success);
        $this->assertSame($currentFilters, $response->currentFilters);
        $this->assertSame('birthdate', $response->sortBy);
        $this->assertSame('desc', $response->sortOrder);
    }
}
