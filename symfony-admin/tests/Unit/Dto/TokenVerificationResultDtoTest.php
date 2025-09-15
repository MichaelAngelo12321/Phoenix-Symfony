<?php

declare(strict_types=1);

namespace App\Tests\Unit\Dto;

use App\Dto\TokenVerificationResultDto;
use PHPUnit\Framework\TestCase;

final class TokenVerificationResultDtoTest extends TestCase
{
    public function testValidCreation(): void
    {
        $admin = ['id' => 1, 'email' => 'admin@example.com', 'role' => 'admin'];

        $dto = TokenVerificationResultDto::valid($admin);

        $this->assertTrue($dto->isValid());
        $this->assertFalse($dto->isFailure());
        $this->assertEquals($admin, $dto->admin);
        $this->assertNull($dto->error);
        $this->assertTrue($dto->success);
        $this->assertTrue($dto->valid);
    }

    public function testInvalidCreation(): void
    {
        $error = 'Token expired';

        $dto = TokenVerificationResultDto::invalid($error);

        $this->assertFalse($dto->isValid());
        $this->assertFalse($dto->isFailure());
        $this->assertEquals($error, $dto->error);
        $this->assertNull($dto->admin);
        $this->assertTrue($dto->success);
        $this->assertFalse($dto->valid);
    }

    public function testInvalidWithDefaultError(): void
    {
        $dto = TokenVerificationResultDto::invalid();

        $this->assertFalse($dto->isValid());
        $this->assertFalse($dto->isFailure());
        $this->assertEquals('Token is invalid', $dto->error);
        $this->assertNull($dto->admin);
        $this->assertTrue($dto->success);
        $this->assertFalse($dto->valid);
    }

    public function testFailureCreation(): void
    {
        $error = 'Network error';

        $dto = TokenVerificationResultDto::failure($error);

        $this->assertFalse($dto->isValid());
        $this->assertTrue($dto->isFailure());
        $this->assertEquals($error, $dto->error);
        $this->assertNull($dto->admin);
        $this->assertFalse($dto->success);
        $this->assertFalse($dto->valid);
    }

    public function testValidWithEmptyAdmin(): void
    {
        $admin = [];

        $dto = TokenVerificationResultDto::valid($admin);

        $this->assertTrue($dto->isValid());
        $this->assertFalse($dto->isFailure());
        $this->assertEquals($admin, $dto->admin);
        $this->assertNull($dto->error);
    }

    public function testIsValidMethod(): void
    {
        $validDto = TokenVerificationResultDto::valid(['id' => 1]);
        $invalidDto = TokenVerificationResultDto::invalid('error');
        $failureDto = TokenVerificationResultDto::failure('error');

        $this->assertTrue($validDto->isValid());
        $this->assertFalse($invalidDto->isValid());
        $this->assertFalse($failureDto->isValid());
    }

    public function testIsFailureMethod(): void
    {
        $validDto = TokenVerificationResultDto::valid(['id' => 1]);
        $invalidDto = TokenVerificationResultDto::invalid('error');
        $failureDto = TokenVerificationResultDto::failure('error');

        $this->assertFalse($validDto->isFailure());
        $this->assertFalse($invalidDto->isFailure());
        $this->assertTrue($failureDto->isFailure());
    }
}
