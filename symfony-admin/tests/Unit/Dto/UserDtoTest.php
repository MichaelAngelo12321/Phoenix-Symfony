<?php

declare(strict_types=1);

namespace App\Tests\Unit\Dto;

use App\Dto\UserDto;
use App\Enum\GenderEnum;
use PHPUnit\Framework\TestCase;

final class UserDtoTest extends TestCase
{
    public function testConstructorWithAllProperties(): void
    {
        $id = 1;
        $firstName = 'John';
        $lastName = 'Doe';
        $birthdate = new \DateTime('1990-01-01');
        $gender = GenderEnum::MALE;

        $dto = new UserDto(
            $id,
            $firstName,
            $lastName,
            $birthdate,
            $gender
        );

        $this->assertEquals($id, $dto->id);
        $this->assertEquals($firstName, $dto->firstName);
        $this->assertEquals($lastName, $dto->lastName);
        $this->assertEquals($birthdate, $dto->birthdate);
        $this->assertEquals($gender, $dto->gender);
    }

    public function testConstructorWithNullableProperties(): void
    {
        $dto = new UserDto();

        $this->assertNull($dto->id);
        $this->assertNull($dto->firstName);
        $this->assertNull($dto->lastName);
        $this->assertNull($dto->birthdate);
        $this->assertNull($dto->gender);
    }

    public function testConstructorWithPartialProperties(): void
    {
        $id = 1;
        $firstName = 'Jane';

        $dto = new UserDto($id, $firstName);

        $this->assertEquals($id, $dto->id);
        $this->assertEquals($firstName, $dto->firstName);
        $this->assertNull($dto->lastName);
        $this->assertNull($dto->birthdate);
        $this->assertNull($dto->gender);
    }

    public function testFromArrayWithAllData(): void
    {
        $data = [
            'id' => 1,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'birthdate' => '1990-01-01',
            'gender' => 'male',
        ];

        $dto = UserDto::fromArray($data);

        $this->assertEquals(1, $dto->id);
        $this->assertEquals('John', $dto->firstName);
        $this->assertEquals('Doe', $dto->lastName);
        $this->assertEquals('1990-01-01', $dto->birthdate->format('Y-m-d'));
        $this->assertEquals(GenderEnum::MALE, $dto->gender);
    }

    public function testFromArrayWithPartialData(): void
    {
        $data = [
            'first_name' => 'Jane',
            'gender' => 'female',
        ];

        $dto = UserDto::fromArray($data);

        $this->assertNull($dto->id);
        $this->assertEquals('Jane', $dto->firstName);
        $this->assertNull($dto->lastName);
        $this->assertNull($dto->birthdate);
        $this->assertEquals(GenderEnum::FEMALE, $dto->gender);
    }

    public function testFromArrayWithEmptyData(): void
    {
        $dto = UserDto::fromArray([]);

        $this->assertNull($dto->id);
        $this->assertNull($dto->firstName);
        $this->assertNull($dto->lastName);
        $this->assertNull($dto->birthdate);
        $this->assertNull($dto->gender);
    }

    public function testToArray(): void
    {
        $dto = new UserDto(
            1,
            'John',
            'Doe',
            new \DateTime('1990-01-01'),
            GenderEnum::MALE
        );

        $array = $dto->toArray();

        $this->assertEquals([
            'id' => 1,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'birthdate' => '1990-01-01',
            'gender' => 'male',
        ], $array);
    }

    public function testToArrayWithNullValues(): void
    {
        $dto = new UserDto(1, 'John');

        $array = $dto->toArray();

        $this->assertEquals([
            'id' => 1,
            'first_name' => 'John',
        ], $array);
    }

    public function testToFormArray(): void
    {
        $birthdate = new \DateTime('1990-01-01');
        $dto = new UserDto(
            1,
            'John',
            'Doe',
            $birthdate,
            GenderEnum::MALE
        );

        $array = $dto->toFormArray();

        $this->assertEquals([
            'id' => 1,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'birthdate' => $birthdate,
            'gender' => 'male',
        ], $array);
    }

    public function testToFormArrayWithNullValues(): void
    {
        $dto = new UserDto(null, 'Jane');

        $array = $dto->toFormArray();

        $this->assertEquals([
            'first_name' => 'Jane',
        ], $array);
    }
}
