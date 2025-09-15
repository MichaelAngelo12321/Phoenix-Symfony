<?php

declare(strict_types=1);

namespace App\Tests\Unit\Dto;

use App\Dto\FilterDto;
use App\Enum\GenderEnum;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

final class FilterDtoTest extends TestCase
{
    public function testFromRequestWithGetParameters(): void
    {
        $request = Request::create('/users', 'GET', [
            'firstName' => 'John',
            'lastName' => 'Doe',
            'gender' => 'male',
            'sortBy' => 'first_name',
            'sortOrder' => 'desc'
        ]);

        $filterDto = FilterDto::fromRequest($request);

        $this->assertSame('John', $filterDto->firstName);
        $this->assertSame('Doe', $filterDto->lastName);
        $this->assertSame(GenderEnum::MALE, $filterDto->gender);
        $this->assertSame('first_name', $filterDto->sortBy);
        $this->assertSame('desc', $filterDto->sortOrder);
    }

    public function testFromRequestWithEmptyData(): void
    {
        $request = Request::create('/users', 'GET');

        $filterDto = FilterDto::fromRequest($request);

        $this->assertNull($filterDto->firstName);
        $this->assertNull($filterDto->lastName);
        $this->assertNull($filterDto->gender);
        $this->assertSame('id', $filterDto->sortBy);
        $this->assertSame('asc', $filterDto->sortOrder);
    }

    public function testFromRequestWithInvalidGender(): void
    {
        $request = Request::create('/users', 'GET', [
            'gender' => 'invalid'
        ]);

        $filterDto = FilterDto::fromRequest($request);

        $this->assertNull($filterDto->gender);
    }

    public function testFromRequestWithNumericGenderValues(): void
    {
        $request = Request::create('/users', 'GET', [
            'gender' => '1'
        ]);

        $filterDto = FilterDto::fromRequest($request);
        $this->assertSame(GenderEnum::MALE, $filterDto->gender);

        $request = Request::create('/users', 'GET', [
            'gender' => '2'
        ]);

        $filterDto = FilterDto::fromRequest($request);
        $this->assertSame(GenderEnum::FEMALE, $filterDto->gender);
    }

    public function testFromRequestWithStringGenderValues(): void
    {
        $request = Request::create('/users', 'GET', [
            'gender' => 'male'
        ]);

        $filterDto = FilterDto::fromRequest($request);
        $this->assertSame(GenderEnum::MALE, $filterDto->gender);

        $request = Request::create('/users', 'GET', [
            'gender' => 'female'
        ]);

        $filterDto = FilterDto::fromRequest($request);
        $this->assertSame(GenderEnum::FEMALE, $filterDto->gender);
    }

    public function testFromRequestWithBirthdates(): void
    {
        $request = Request::create('/users', 'GET', [
            'birthdateFrom' => '1990-01-01',
            'birthdateTo' => '2000-12-31'
        ]);

        $filterDto = FilterDto::fromRequest($request);

        $this->assertInstanceOf(\DateTimeInterface::class, $filterDto->birthdateFrom);
        $this->assertInstanceOf(\DateTimeInterface::class, $filterDto->birthdateTo);
        $this->assertSame('1990-01-01', $filterDto->birthdateFrom->format('Y-m-d'));
        $this->assertSame('2000-12-31', $filterDto->birthdateTo->format('Y-m-d'));
    }

    public function testFromRequestWithInvalidBirthdates(): void
    {
        $request = Request::create('/users', 'GET', [
            'birthdateFrom' => 'invalid-date',
            'birthdateTo' => 'also-invalid'
        ]);

        $filterDto = FilterDto::fromRequest($request);

        $this->assertNull($filterDto->birthdateFrom);
        $this->assertNull($filterDto->birthdateTo);
    }

    public function testValidateSortBy(): void
    {
        $request = Request::create('/users', 'GET', [
            'sortBy' => 'invalid_field'
        ]);

        $filterDto = FilterDto::fromRequest($request);

        $this->assertSame('id', $filterDto->sortBy);
    }

    public function testValidateSortOrder(): void
    {
        $request = Request::create('/users', 'GET', [
            'sortOrder' => 'invalid_order'
        ]);

        $filterDto = FilterDto::fromRequest($request);

        $this->assertSame('asc', $filterDto->sortOrder);
    }

    public function testToArray(): void
    {
        $filterDto = new FilterDto(
            firstName: 'John',
            lastName: 'Doe',
            gender: GenderEnum::MALE,
            sortBy: 'first_name',
            sortOrder: 'desc'
        );

        $array = $filterDto->toArray();

        $this->assertSame('John', $array['first_name']);
        $this->assertSame('Doe', $array['last_name']);
        $this->assertSame('male', $array['gender']);
        $this->assertSame('first_name', $array['sort_by']);
        $this->assertSame('desc', $array['sort_order']);
    }

    public function testHasFilters(): void
    {
        $emptyFilterDto = new FilterDto();
        $this->assertFalse($emptyFilterDto->hasFilters());

        $filterDtoWithName = new FilterDto(firstName: 'John');
        $this->assertTrue($filterDtoWithName->hasFilters());

        $filterDtoWithGender = new FilterDto(gender: GenderEnum::MALE);
        $this->assertTrue($filterDtoWithGender->hasFilters());
    }

    public function testGetCurrentFilters(): void
    {
        $filterDto = new FilterDto(
            firstName: 'John',
            gender: GenderEnum::FEMALE
        );

        $currentFilters = $filterDto->getCurrentFilters();

        $this->assertSame('John', $currentFilters['first_name']);
        $this->assertNull($currentFilters['last_name']);
        $this->assertSame('female', $currentFilters['gender']);
    }
}