<?php

declare(strict_types=1);

namespace App\Tests\Unit\Dto;

use App\Dto\AuthenticationResultDto;
use PHPUnit\Framework\TestCase;

final class AuthenticationResultDtoTest extends TestCase
{
    public function testSuccessCreation(): void
    {
        $token = 'test-token-123';
        $admin = ['id' => 1, 'email' => 'admin@example.com'];
        
        $dto = AuthenticationResultDto::success($token, $admin);
        
        $this->assertTrue($dto->isSuccess());
        $this->assertFalse($dto->isFailure());
        $this->assertEquals($token, $dto->token);
        $this->assertEquals($admin, $dto->admin);
        $this->assertNull($dto->error);
    }

    public function testFailureCreation(): void
    {
        $error = 'Invalid credentials';
        
        $dto = AuthenticationResultDto::failure($error);
        
        $this->assertFalse($dto->isSuccess());
        $this->assertTrue($dto->isFailure());
        $this->assertEquals($error, $dto->error);
        $this->assertNull($dto->token);
        $this->assertNull($dto->admin);
    }

    public function testSuccessWithEmptyAdmin(): void
    {
        $token = 'test-token';
        $admin = [];
        
        $dto = AuthenticationResultDto::success($token, $admin);
        
        $this->assertTrue($dto->isSuccess());
        $this->assertFalse($dto->isFailure());
        $this->assertEquals($token, $dto->token);
        $this->assertEquals($admin, $dto->admin);
        $this->assertNull($dto->error);
    }

    public function testIsSuccessMethod(): void
    {
        $successDto = AuthenticationResultDto::success('token', ['id' => 1]);
        $failureDto = AuthenticationResultDto::failure('error');
        
        $this->assertTrue($successDto->isSuccess());
        $this->assertFalse($failureDto->isSuccess());
    }

    public function testIsFailureMethod(): void
    {
        $successDto = AuthenticationResultDto::success('token', ['id' => 1]);
        $failureDto = AuthenticationResultDto::failure('error');
        
        $this->assertFalse($successDto->isFailure());
        $this->assertTrue($failureDto->isFailure());
    }
}