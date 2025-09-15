<?php

declare(strict_types=1);

namespace App\Tests\Unit\Enum;

use App\Enum\FlashType;
use PHPUnit\Framework\TestCase;

final class FlashTypeTest extends TestCase
{
    public function testFlashTypeValues(): void
    {
        $this->assertSame('success', FlashType::SUCCESS->value);
        $this->assertSame('error', FlashType::ERROR->value);
    }

    public function testFlashTypeCases(): void
    {
        $cases = FlashType::cases();
        
        $this->assertCount(2, $cases);
        $this->assertContains(FlashType::SUCCESS, $cases);
        $this->assertContains(FlashType::ERROR, $cases);
    }

    public function testFlashTypeFromValue(): void
    {
        $this->assertSame(FlashType::SUCCESS, FlashType::from('success'));
        $this->assertSame(FlashType::ERROR, FlashType::from('error'));
    }

    public function testFlashTypeTryFromValue(): void
    {
        $this->assertSame(FlashType::SUCCESS, FlashType::tryFrom('success'));
        $this->assertSame(FlashType::ERROR, FlashType::tryFrom('error'));
        $this->assertNull(FlashType::tryFrom('invalid'));
        $this->assertNull(FlashType::tryFrom(''));
        $this->assertNull(FlashType::tryFrom('warning'));
    }

    public function testFlashTypeName(): void
    {
        $this->assertSame('SUCCESS', FlashType::SUCCESS->name);
        $this->assertSame('ERROR', FlashType::ERROR->name);
    }
}