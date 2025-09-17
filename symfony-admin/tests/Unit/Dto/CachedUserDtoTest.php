<?php

declare(strict_types=1);

namespace App\Tests\Unit\Dto;

use App\Dto\CachedUserDto;
use App\Enum\GenderEnum;
use PHPUnit\Framework\TestCase;

final class CachedUserDtoTest extends TestCase
{
    public function testFromApiResponseCreatesCorrectDto(): void
    {
        // Arrange
        $apiData = [
            'id' => 1,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'birthdate' => '1990-05-15',
            'gender' => 'male'
        ];

        // Act
        $dto = CachedUserDto::fromApiResponse($apiData);

        // Assert
        $this->assertEquals(1, $dto->id);
        $this->assertEquals('John', $dto->firstName);
        $this->assertEquals('Doe', $dto->lastName);
        $this->assertEquals('1990-05-15', $dto->birthdate?->format('Y-m-d'));
        $this->assertEquals(GenderEnum::MALE, $dto->gender);
    }

    public function testFromApiResponseHandlesMissingFields(): void
    {
        // Arrange
        $apiData = [
            'id' => 2,
            'first_name' => 'Jane'
        ];

        // Act
        $dto = CachedUserDto::fromApiResponse($apiData);

        // Assert
        $this->assertEquals(2, $dto->id);
        $this->assertEquals('Jane', $dto->firstName);
        $this->assertNull($dto->lastName);
        $this->assertNull($dto->birthdate);
        $this->assertNull($dto->gender);
    }

    public function testToArrayReturnsCorrectStructure(): void
    {
        // Arrange
        $dto = new CachedUserDto(
            id: 1,
            firstName: 'John',
            lastName: 'Doe',
            birthdate: new \DateTimeImmutable('1990-05-15'),
            gender: GenderEnum::MALE,
            cacheTimestamp: 1234567890
        );

        // Act
        $array = $dto->toArray();

        // Assert
        $expected = [
            'id' => 1,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'birthdate' => '1990-05-15',
            'gender' => 'male',
            '_cache_timestamp' => 1234567890
        ];
        $this->assertEquals($expected, $array);
    }

    public function testGetFullNameCombinesFirstAndLastName(): void
    {
        // Arrange
        $dto = new CachedUserDto(
            id: 1,
            firstName: 'John',
            lastName: 'Doe'
        );

        // Act
        $fullName = $dto->getFullName();

        // Assert
        $this->assertEquals('John Doe', $fullName);
    }

    public function testGetFullNameHandlesNullValues(): void
    {
        // Arrange
        $dto = new CachedUserDto(
            id: 1,
            firstName: 'John',
            lastName: null
        );

        // Act
        $fullName = $dto->getFullName();

        // Assert
        $this->assertEquals('John', $fullName);
    }

    public function testMatchesFilterForFirstName(): void
    {
        // Arrange
        $dto = new CachedUserDto(
            id: 1,
            firstName: 'John',
            lastName: 'Doe'
        );

        // Act & Assert
        $this->assertTrue($dto->matchesFilter('firstName', 'john'));
        $this->assertTrue($dto->matchesFilter('firstName', 'Jo'));
        $this->assertFalse($dto->matchesFilter('firstName', 'Jane'));
    }

    public function testMatchesFilterForLastName(): void
    {
        // Arrange
        $dto = new CachedUserDto(
            id: 1,
            firstName: 'John',
            lastName: 'Doe'
        );

        // Act & Assert
        $this->assertTrue($dto->matchesFilter('lastName', 'doe'));
        $this->assertTrue($dto->matchesFilter('lastName', 'Do'));
        $this->assertFalse($dto->matchesFilter('lastName', 'Smith'));
    }

    public function testMatchesFilterForGender(): void
    {
        // Arrange
        $dto = new CachedUserDto(
            id: 1,
            firstName: 'John',
            lastName: 'Doe',
            gender: GenderEnum::MALE
        );

        // Act & Assert
        $this->assertTrue($dto->matchesFilter('gender', 'male'));
        $this->assertFalse($dto->matchesFilter('gender', 'female'));
    }

    public function testMatchesFilterForBirthdateRange(): void
    {
        // Arrange
        $dto = new CachedUserDto(
            id: 1,
            firstName: 'John',
            lastName: 'Doe',
            birthdate: new \DateTimeImmutable('1990-05-15')
        );

        // Act & Assert
        $this->assertTrue($dto->matchesFilter('birthdateFrom', new \DateTimeImmutable('1990-01-01')));
        $this->assertTrue($dto->matchesFilter('birthdateTo', new \DateTimeImmutable('1990-12-31')));
        $this->assertFalse($dto->matchesFilter('birthdateFrom', new \DateTimeImmutable('1991-01-01')));
        $this->assertFalse($dto->matchesFilter('birthdateTo', new \DateTimeImmutable('1989-12-31')));
    }

    public function testGetSortValueReturnsCorrectValues(): void
    {
        // Arrange
        $dto = new CachedUserDto(
            id: 1,
            firstName: 'John',
            lastName: 'Doe',
            birthdate: new \DateTimeImmutable('1990-05-15'),
            gender: GenderEnum::MALE
        );

        // Act & Assert
        $this->assertEquals('John', $dto->getSortValue('firstName'));
        $this->assertEquals('Doe', $dto->getSortValue('lastName'));
        $this->assertEquals(1, $dto->getSortValue('id'));
        $this->assertEquals('male', $dto->getSortValue('gender'));
        $this->assertEquals(642729600, $dto->getSortValue('birthdate')); // timestamp for 1990-05-15
        $this->assertEquals('', $dto->getSortValue('unknown'));
    }

    public function testGetSortValueHandlesNullValues(): void
    {
        // Arrange
        $dto = new CachedUserDto(
            id: 1,
            firstName: null,
            lastName: null,
            birthdate: null,
            gender: null
        );

        // Act & Assert
        $this->assertEquals('', $dto->getSortValue('firstName'));
        $this->assertEquals('', $dto->getSortValue('lastName'));
        $this->assertEquals('', $dto->getSortValue('gender'));
        $this->assertEquals(0, $dto->getSortValue('birthdate'));
    }
}