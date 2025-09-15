<?php

declare(strict_types=1);

namespace App\Tests\Unit\Dto;

use App\Dto\UserRequestDto;
use App\Enum\GenderEnum;
use App\Enum\ValidatorMessage;
use PHPUnit\Framework\TestCase;

final class UserRequestDtoTest extends TestCase
{
    public function testConstructorWithAllProperties(): void
    {
        $firstName = 'John';
        $lastName = 'Doe';
        $birthdate = new \DateTime('1990-01-01');
        $gender = GenderEnum::MALE;

        $dto = new UserRequestDto(
            $firstName,
            $lastName,
            $birthdate,
            $gender
        );

        $this->assertEquals($firstName, $dto->firstName);
        $this->assertEquals($lastName, $dto->lastName);
        $this->assertEquals($birthdate, $dto->birthdate);
        $this->assertEquals($gender, $dto->gender);
    }

    public function testConstructorWithNullableProperties(): void
    {
        $dto = new UserRequestDto();

        $this->assertNull($dto->firstName);
        $this->assertNull($dto->lastName);
        $this->assertNull($dto->birthdate);
        $this->assertNull($dto->gender);
    }

    public function testConstructorWithPartialProperties(): void
    {
        $firstName = 'Jane';
        $gender = GenderEnum::FEMALE;

        $dto = new UserRequestDto($firstName, null, null, $gender);

        $this->assertEquals($firstName, $dto->firstName);
        $this->assertNull($dto->lastName);
        $this->assertNull($dto->birthdate);
        $this->assertEquals($gender, $dto->gender);
    }

    public function testFromArrayWithAllData(): void
    {
        $data = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'birthdate' => new \DateTime('1990-01-01'),
            'gender' => 'male',
        ];

        $dto = UserRequestDto::fromArray($data);

        $this->assertEquals('John', $dto->firstName);
        $this->assertEquals('Doe', $dto->lastName);
        $this->assertEquals($data['birthdate'], $dto->birthdate);
        $this->assertEquals(GenderEnum::MALE, $dto->gender);
    }

    public function testFromArrayWithPartialData(): void
    {
        $data = [
            'first_name' => 'Jane',
            'gender' => 'female',
        ];

        $dto = UserRequestDto::fromArray($data);

        $this->assertEquals('Jane', $dto->firstName);
        $this->assertNull($dto->lastName);
        $this->assertNull($dto->birthdate);
        $this->assertEquals(GenderEnum::FEMALE, $dto->gender);
    }

    public function testFromArrayWithEmptyData(): void
    {
        $dto = UserRequestDto::fromArray([]);

        $this->assertNull($dto->firstName);
        $this->assertNull($dto->lastName);
        $this->assertNull($dto->birthdate);
        $this->assertNull($dto->gender);
    }

    public function testToArray(): void
    {
        $dto = new UserRequestDto(
            'John',
            'Doe',
            new \DateTime('1990-01-01'),
            GenderEnum::MALE
        );

        $array = $dto->toArray();

        $this->assertEquals([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'birthdate' => '1990-01-01',
            'gender' => 'male',
        ], $array);
    }

    public function testToArrayWithNullValues(): void
    {
        $dto = new UserRequestDto('John');

        $array = $dto->toArray();

        $this->assertEquals([
            'first_name' => 'John',
        ], $array);
    }

    public function testIsValidWithAllData(): void
    {
        $dto = new UserRequestDto(
            'John',
            'Doe',
            new \DateTime('1990-01-01'),
            GenderEnum::MALE
        );

        $this->assertTrue($dto->isValid());
    }

    public function testIsValidWithMissingData(): void
    {
        $dto = new UserRequestDto('John');

        $this->assertFalse($dto->isValid());
    }

    public function testGetValidationErrorsWithValidData(): void
    {
        $dto = new UserRequestDto(
            'John',
            'Doe',
            new \DateTime('1990-01-01'),
            GenderEnum::MALE
        );

        $errors = $dto->getValidationErrors();

        $this->assertEmpty($errors);
    }

    public function testGetValidationErrorsWithInvalidData(): void
    {
        $dto = new UserRequestDto();

        $errors = $dto->getValidationErrors();

        $this->assertArrayHasKey('firstName', $errors);
        $this->assertArrayHasKey('lastName', $errors);
        $this->assertArrayHasKey('birthdate', $errors);
        $this->assertArrayHasKey('gender', $errors);
        $this->assertEquals(ValidatorMessage::FIRST_NAME_REQUIRED->value, $errors['firstName']);
        $this->assertEquals(ValidatorMessage::LAST_NAME_REQUIRED->value, $errors['lastName']);
        $this->assertEquals(ValidatorMessage::BIRTHDATE_REQUIRED->value, $errors['birthdate']);
        $this->assertEquals(ValidatorMessage::GENDER_REQUIRED->value, $errors['gender']);
    }

    public function testGetValidationErrorsWithShortNames(): void
    {
        $dto = new UserRequestDto('J', 'D');

        $errors = $dto->getValidationErrors();

        $this->assertArrayHasKey('firstName', $errors);
        $this->assertArrayHasKey('lastName', $errors);
        $this->assertEquals(ValidatorMessage::FIRST_NAME_TOO_SHORT->value, $errors['firstName']);
        $this->assertEquals(ValidatorMessage::LAST_NAME_TOO_SHORT->value, $errors['lastName']);
    }

    public function testGetValidationErrorsWithFutureBirthdate(): void
    {
        $futureDate = new \DateTime('+1 year');
        $dto = new UserRequestDto('John', 'Doe', $futureDate, GenderEnum::MALE);

        $errors = $dto->getValidationErrors();

        $this->assertArrayHasKey('birthdate', $errors);
        $this->assertEquals(ValidatorMessage::BIRTHDATE_FUTURE->value, $errors['birthdate']);
    }

    public function testReadonlyProperties(): void
    {
        $dto = new UserRequestDto(
            'Test',
            'User',
            new \DateTime('1985-05-15'),
            GenderEnum::FEMALE
        );

        $this->assertEquals('Test', $dto->firstName);
        $this->assertEquals('User', $dto->lastName);
        $this->assertEquals('1985-05-15', $dto->birthdate->format('Y-m-d'));
        $this->assertEquals(GenderEnum::FEMALE, $dto->gender);
    }
}
