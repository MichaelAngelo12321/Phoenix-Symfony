<?php

declare(strict_types=1);

namespace App\Tests\Unit\Dto;

use App\Dto\UserDto;
use App\Dto\UserListResponseDto;
use App\Enum\GenderEnum;
use PHPUnit\Framework\TestCase;

final class UserListResponseDtoTest extends TestCase
{
    public function testSuccessCreation(): void
    {
        $users = [
            new UserDto(1, 'John', 'Doe'),
            new UserDto(2, 'Jane', 'Smith'),
        ];
        $currentFilters = ['gender' => 'male'];
        $sortBy = 'firstName';
        $sortOrder = 'desc';

        $dto = UserListResponseDto::success($users, $currentFilters, $sortBy, $sortOrder);

        $this->assertTrue($dto->isSuccess());
        $this->assertEquals($users, $dto->users);
        $this->assertEquals($currentFilters, $dto->currentFilters);
        $this->assertEquals($sortBy, $dto->sortBy);
        $this->assertEquals($sortOrder, $dto->sortOrder);
        $this->assertEquals([], $dto->errors);
        $this->assertTrue($dto->isApiAvailable());
    }

    public function testSuccessWithDefaults(): void
    {
        $users = [new UserDto(1, 'John', 'Doe')];

        $dto = UserListResponseDto::success($users);

        $this->assertTrue($dto->isSuccess());
        $this->assertEquals($users, $dto->users);
        $this->assertEquals([], $dto->currentFilters);
        $this->assertEquals('id', $dto->sortBy);
        $this->assertEquals('asc', $dto->sortOrder);
        $this->assertEquals([], $dto->errors);
        $this->assertTrue($dto->isApiAvailable());
    }

    public function testSuccessWithEmptyUsers(): void
    {
        $dto = UserListResponseDto::success([]);

        $this->assertTrue($dto->isSuccess());
        $this->assertEquals([], $dto->users);
        $this->assertEquals([], $dto->currentFilters);
        $this->assertEquals('id', $dto->sortBy);
        $this->assertEquals('asc', $dto->sortOrder);
        $this->assertEquals([], $dto->errors);
        $this->assertTrue($dto->isApiAvailable());
    }

    public function testFailureCreation(): void
    {
        $errors = ['Invalid request', 'Missing parameter'];

        $dto = UserListResponseDto::failure($errors);

        $this->assertFalse($dto->isSuccess());
        $this->assertEquals([], $dto->users);
        $this->assertEquals($errors, $dto->errors);
        $this->assertEquals($errors, $dto->getErrors());
        $this->assertTrue($dto->isApiAvailable());
    }

    public function testFailureWithApiUnavailable(): void
    {
        $errors = ['Service down'];

        $dto = UserListResponseDto::failure($errors, false);

        $this->assertFalse($dto->isSuccess());
        $this->assertEquals([], $dto->users);
        $this->assertEquals($errors, $dto->errors);
        $this->assertFalse($dto->isApiAvailable());
    }

    public function testApiUnavailable(): void
    {
        $dto = UserListResponseDto::apiUnavailable();

        $this->assertFalse($dto->isSuccess());
        $this->assertEquals([], $dto->users);
        $this->assertEquals(['API jest niedostępne'], $dto->errors);
        $this->assertFalse($dto->isApiAvailable());
    }

    public function testApiUnavailableWithCustomError(): void
    {
        $error = 'Custom API error';

        $dto = UserListResponseDto::apiUnavailable($error);

        $this->assertFalse($dto->isSuccess());
        $this->assertEquals([], $dto->users);
        $this->assertEquals([$error], $dto->errors);
        $this->assertFalse($dto->isApiAvailable());
    }

    public function testToArray(): void
    {
        $users = [
            new UserDto(1, 'John', 'Doe', null, GenderEnum::MALE),
            new UserDto(2, 'Jane', 'Smith', null, GenderEnum::FEMALE),
        ];
        $currentFilters = ['gender' => 'male'];

        $dto = UserListResponseDto::success($users, $currentFilters, 'firstName', 'desc');

        $array = $dto->toArray();

        $this->assertEquals([
            'success' => true,
            'users' => [
                ['id' => 1, 'first_name' => 'John', 'last_name' => 'Doe', 'gender' => 'male'],
                ['id' => 2, 'first_name' => 'Jane', 'last_name' => 'Smith', 'gender' => 'female'],
            ],
            'errors' => [],
            'api_available' => true,
            'current_filters' => ['gender' => 'male'],
            'sort_by' => 'firstName',
            'sort_order' => 'desc',
        ], $array);
    }

    public function testToArrayWithFailure(): void
    {
        $errors = ['Error message'];
        $dto = UserListResponseDto::failure($errors, false);

        $array = $dto->toArray();

        $this->assertEquals([
            'success' => false,
            'users' => [],
            'errors' => ['Error message'],
            'api_available' => false,
            'current_filters' => [],
            'sort_by' => 'id',
            'sort_order' => 'asc',
        ], $array);
    }
}
