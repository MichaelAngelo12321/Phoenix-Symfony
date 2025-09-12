<?php

declare(strict_types=1);

namespace App\Dto;

final readonly class AuthenticationResultDto
{
    public function __construct(
        public bool $success,
        public ?string $token = null,
        public ?array $admin = null,
        public ?string $error = null
    ) {
    }

    public static function success(string $token, array $admin): self
    {
        return new self(
            success: true,
            token: $token,
            admin: $admin
        );
    }

    public static function failure(string $error): self
    {
        return new self(
            success: false,
            error: $error
        );
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function isFailure(): bool
    {
        return ! $this->success;
    }
}
