<?php

declare(strict_types=1);

namespace App\Tests\Unit\Enum;

use App\Enum\GenderEnum;
use PHPUnit\Framework\TestCase;

final class GenderEnumTest extends TestCase
{
    public function testFromStringWithNumericValues(): void
    {
        $this->assertSame(GenderEnum::MALE, GenderEnum::fromString('1'));
        $this->assertSame(GenderEnum::FEMALE, GenderEnum::fromString('2'));
    }

    public function testFromStringWithStringValues(): void
    {
        $this->assertSame(GenderEnum::MALE, GenderEnum::fromString('male'));
        $this->assertSame(GenderEnum::FEMALE, GenderEnum::fromString('female'));
    }

    public function testFromStringWithNullValue(): void
    {
        $this->assertNull(GenderEnum::fromString(null));
    }

    public function testFromStringWithEmptyString(): void
    {
        $this->assertNull(GenderEnum::fromString(''));
    }

    public function testFromStringWithInvalidValue(): void
    {
        $this->assertNull(GenderEnum::fromString('invalid'));
        $this->assertNull(GenderEnum::fromString('3'));
        $this->assertNull(GenderEnum::fromString('0'));
    }

    public function testEnumValues(): void
    {
        $this->assertSame('male', GenderEnum::MALE->value);
        $this->assertSame('female', GenderEnum::FEMALE->value);
    }

    public function testGetLabel(): void
    {
        $this->assertSame('Mężczyzna', GenderEnum::MALE->getLabel());
        $this->assertSame('Kobieta', GenderEnum::FEMALE->getLabel());
    }

    public function testValues(): void
    {
        $values = GenderEnum::values();

        $this->assertCount(2, $values);
        $this->assertContains('male', $values);
        $this->assertContains('female', $values);
    }

    public function testMatchExpressionCoverage(): void
    {
        $testCases = [
            [null, null],
            ['', null],
            ['1', GenderEnum::MALE],
            ['2', GenderEnum::FEMALE],
            ['male', GenderEnum::MALE],
            ['female', GenderEnum::FEMALE],
            ['invalid', null],
            ['3', null],
            ['0', null],
            ['MALE', null],
            ['FEMALE', null],
        ];

        foreach ($testCases as [$input, $expected]) {
            $this->assertSame(
                $expected,
                GenderEnum::fromString($input),
                'Failed for input: ' . var_export($input, true)
            );
        }
    }
}
