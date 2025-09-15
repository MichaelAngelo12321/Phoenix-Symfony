<?php

declare(strict_types=1);

namespace App\Tests\Unit\Mapper;

use App\Dto\UserDto;
use App\Dto\UserRequestDto;
use App\Enum\GenderEnum;
use App\Mapper\UserMapper;
use PHPUnit\Framework\TestCase;

final class UserMapperTest extends TestCase
{
    public function testMapApiResponseToUserDtoWithCompleteData(): void
    {
        $apiData = [
            'id' => 123,
            'first_name' => 'Jan',
            'last_name' => 'Kowalski',
            'birthdate' => '1990-05-15',
            'gender' => 'male',
        ];

        $userDto = UserMapper::mapApiResponseToUserDto($apiData);

        $this->assertInstanceOf(UserDto::class, $userDto);
        $this->assertSame(123, $userDto->id);
        $this->assertSame('Jan', $userDto->firstName);
        $this->assertSame('Kowalski', $userDto->lastName);
        $this->assertInstanceOf(\DateTimeInterface::class, $userDto->birthdate);
        $this->assertSame('1990-05-15', $userDto->birthdate->format('Y-m-d'));
        $this->assertSame(GenderEnum::MALE, $userDto->gender);
    }

    public function testMapApiResponseToUserDtoWithPartialData(): void
    {
        $apiData = [
            'id' => 456,
            'first_name' => 'Anna',
        ];

        $userDto = UserMapper::mapApiResponseToUserDto($apiData);

        $this->assertInstanceOf(UserDto::class, $userDto);
        $this->assertSame(456, $userDto->id);
        $this->assertSame('Anna', $userDto->firstName);
        $this->assertNull($userDto->lastName);
        $this->assertNull($userDto->birthdate);
        $this->assertNull($userDto->gender);
    }

    public function testMapApiResponseToUserDtoWithEmptyArray(): void
    {
        $userDto = UserMapper::mapApiResponseToUserDto([]);

        $this->assertNull($userDto);
    }

    public function testMapApiResponseToUserDtoWithInvalidDate(): void
    {
        $apiData = [
            'id' => 789,
            'first_name' => 'Piotr',
            'birthdate' => 'invalid-date',
        ];

        $userDto = UserMapper::mapApiResponseToUserDto($apiData);

        $this->assertInstanceOf(UserDto::class, $userDto);
        $this->assertSame(789, $userDto->id);
        $this->assertSame('Piotr', $userDto->firstName);
        $this->assertNull($userDto->birthdate);
    }

    public function testMapApiResponseArrayToUserDtos(): void
    {
        $apiDataArray = [
            [
                'id' => 1,
                'first_name' => 'Jan',
                'last_name' => 'Kowalski',
                'gender' => 'male',
            ],
            [
                'id' => 2,
                'first_name' => 'Anna',
                'last_name' => 'Nowak',
                'gender' => 'female',
            ],
        ];

        $userDtos = UserMapper::mapApiResponseArrayToUserDtos($apiDataArray);

        $this->assertCount(2, $userDtos);
        $this->assertInstanceOf(UserDto::class, $userDtos[0]);
        $this->assertInstanceOf(UserDto::class, $userDtos[1]);
        $this->assertSame('Jan', $userDtos[0]->firstName);
        $this->assertSame('Anna', $userDtos[1]->firstName);
    }

    public function testMapApiResponseArrayToUserDtosWithInvalidData(): void
    {
        $apiDataArray = [
            [
                'id' => 1,
                'first_name' => 'Jan',
            ],
            'invalid-data',
            [],
            [
                'id' => 2,
                'first_name' => 'Anna',
            ],
        ];

        $userDtos = UserMapper::mapApiResponseArrayToUserDtos($apiDataArray);

        $this->assertCount(2, $userDtos);
        $this->assertInstanceOf(UserDto::class, $userDtos[0]);
        $this->assertInstanceOf(UserDto::class, $userDtos[1]);
        $this->assertSame('Jan', $userDtos[0]->firstName);
        $this->assertSame('Anna', $userDtos[1]->firstName);
    }

    public function testMapFormDataToUserRequestDto(): void
    {
        $birthdate = new \DateTime('1985-12-03');
        $formData = [
            'first_name' => 'Tomasz',
            'last_name' => 'Wiśniewski',
            'birthdate' => $birthdate,
            'gender' => 'male',
        ];

        $userRequestDto = UserMapper::mapFormDataToUserRequestDto($formData);

        $this->assertInstanceOf(UserRequestDto::class, $userRequestDto);
        $this->assertSame('Tomasz', $userRequestDto->firstName);
        $this->assertSame('Wiśniewski', $userRequestDto->lastName);
        $this->assertSame($birthdate, $userRequestDto->birthdate);
        $this->assertSame(GenderEnum::MALE, $userRequestDto->gender);
    }

    public function testMapFormDataToUserRequestDtoWithEmptyData(): void
    {
        $userRequestDto = UserMapper::mapFormDataToUserRequestDto([]);

        $this->assertInstanceOf(UserRequestDto::class, $userRequestDto);
        $this->assertNull($userRequestDto->firstName);
        $this->assertNull($userRequestDto->lastName);
        $this->assertNull($userRequestDto->birthdate);
        $this->assertNull($userRequestDto->gender);
    }

    public function testMapUserDtoToApiRequest(): void
    {
        $birthdate = new \DateTime('1990-05-15');
        $userDto = new UserDto(
            id: 123,
            firstName: 'jan',
            lastName: 'kowalski',
            birthdate: $birthdate,
            gender: GenderEnum::MALE
        );

        $apiRequest = UserMapper::mapUserDtoToApiRequest($userDto);

        $this->assertSame([
            'first_name' => 'JAN',
            'last_name' => 'KOWALSKI',
            'birthdate' => '1990-05-15',
            'gender' => 'male',
        ], $apiRequest);
    }

    public function testMapUserDtoToApiRequestWithNullValues(): void
    {
        $userDto = new UserDto(
            id: 123,
            firstName: null,
            lastName: 'kowalski',
            birthdate: null,
            gender: null
        );

        $apiRequest = UserMapper::mapUserDtoToApiRequest($userDto);

        $this->assertSame([
            'last_name' => 'KOWALSKI',
        ], $apiRequest);
    }

    public function testMapUserRequestDtoToApiRequest(): void
    {
        $birthdate = new \DateTime('1995-08-20');
        $userRequestDto = new UserRequestDto(
            firstName: 'anna',
            lastName: 'nowak',
            birthdate: $birthdate,
            gender: GenderEnum::FEMALE
        );

        $apiRequest = UserMapper::mapUserRequestDtoToApiRequest($userRequestDto);

        $this->assertSame([
            'first_name' => 'ANNA',
            'last_name' => 'NOWAK',
            'birthdate' => '1995-08-20',
            'gender' => 'female',
        ], $apiRequest);
    }

    public function testMapApiErrorToErrorsWithValidationError(): void
    {
        $validationJson = '{"first_name":["is required"],"last_name":["is too short","must contain only letters"]}';
        $exception = new \Exception("Validation error: {$validationJson}");

        $errors = UserMapper::mapApiErrorToErrors($exception);

        $this->assertCount(3, $errors);
        $this->assertContains('first_name: is required', $errors);
        $this->assertContains('last_name: is too short', $errors);
        $this->assertContains('last_name: must contain only letters', $errors);
    }

    public function testMapApiErrorToErrorsWithInvalidJson(): void
    {
        $exception = new \Exception('Validation error: invalid-json');

        $errors = UserMapper::mapApiErrorToErrors($exception);

        $this->assertCount(1, $errors);
        $this->assertSame('Validation error: invalid-json', $errors[0]);
    }

    public function testMapApiErrorToErrorsWithRegularError(): void
    {
        $exception = new \Exception('Network connection failed');

        $errors = UserMapper::mapApiErrorToErrors($exception);

        $this->assertCount(1, $errors);
        $this->assertSame('Network connection failed', $errors[0]);
    }

    public function testMapApiErrorToErrorsWithStringFieldError(): void
    {
        $validationJson = '{"email":"Invalid email format"}';
        $exception = new \Exception("Validation error: {$validationJson}");

        $errors = UserMapper::mapApiErrorToErrors($exception);

        $this->assertCount(1, $errors);
        $this->assertSame('email: Invalid email format', $errors[0]);
    }

    public function testMapUserFieldsToApiDataConvertsNamesToUppercase(): void
    {
        $birthdate = new \DateTime('2000-01-01');
        $userDto = new UserDto(
            firstName: 'małgorzata',
            lastName: 'kowalczyk-nowak',
            birthdate: $birthdate,
            gender: GenderEnum::FEMALE
        );

        $apiRequest = UserMapper::mapUserDtoToApiRequest($userDto);

        $this->assertSame('MAłGORZATA', $apiRequest['first_name']);
        $this->assertSame('KOWALCZYK-NOWAK', $apiRequest['last_name']);
    }

    public function testMapUserFieldsToApiDataFormatsDateCorrectly(): void
    {
        $birthdate = new \DateTime('1975-12-31 15:30:45');
        $userDto = new UserDto(
            firstName: 'Test',
            birthdate: $birthdate
        );

        $apiRequest = UserMapper::mapUserDtoToApiRequest($userDto);

        $this->assertSame('1975-12-31', $apiRequest['birthdate']);
    }
}