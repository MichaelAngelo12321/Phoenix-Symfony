<?php

declare(strict_types=1);

namespace App\Tests\Unit\Enum;

use App\Enum\HttpStatus;
use PHPUnit\Framework\TestCase;

final class HttpStatusTest extends TestCase
{
    public function testHttpStatusValues(): void
    {
        $this->assertSame(200, HttpStatus::OK->value);
        $this->assertSame(201, HttpStatus::CREATED->value);
        $this->assertSame(204, HttpStatus::NO_CONTENT->value);
        $this->assertSame(404, HttpStatus::NOT_FOUND->value);
        $this->assertSame(422, HttpStatus::UNPROCESSABLE_ENTITY->value);
    }

    public function testHttpStatusCases(): void
    {
        $cases = HttpStatus::cases();
        
        $this->assertCount(5, $cases);
        $this->assertContains(HttpStatus::OK, $cases);
        $this->assertContains(HttpStatus::CREATED, $cases);
        $this->assertContains(HttpStatus::NO_CONTENT, $cases);
        $this->assertContains(HttpStatus::NOT_FOUND, $cases);
        $this->assertContains(HttpStatus::UNPROCESSABLE_ENTITY, $cases);
    }

    public function testHttpStatusFromValue(): void
    {
        $this->assertSame(HttpStatus::OK, HttpStatus::from(200));
        $this->assertSame(HttpStatus::CREATED, HttpStatus::from(201));
        $this->assertSame(HttpStatus::NO_CONTENT, HttpStatus::from(204));
        $this->assertSame(HttpStatus::NOT_FOUND, HttpStatus::from(404));
        $this->assertSame(HttpStatus::UNPROCESSABLE_ENTITY, HttpStatus::from(422));
    }

    public function testHttpStatusTryFromValue(): void
    {
        $this->assertSame(HttpStatus::OK, HttpStatus::tryFrom(200));
        $this->assertSame(HttpStatus::CREATED, HttpStatus::tryFrom(201));
        $this->assertNull(HttpStatus::tryFrom(500));
        $this->assertNull(HttpStatus::tryFrom(999));
    }

    public function testHttpStatusName(): void
    {
        $this->assertSame('OK', HttpStatus::OK->name);
        $this->assertSame('CREATED', HttpStatus::CREATED->name);
        $this->assertSame('NO_CONTENT', HttpStatus::NO_CONTENT->name);
        $this->assertSame('NOT_FOUND', HttpStatus::NOT_FOUND->name);
        $this->assertSame('UNPROCESSABLE_ENTITY', HttpStatus::UNPROCESSABLE_ENTITY->name);
    }
}