<?php

declare(strict_types=1);

namespace App\Dto;

final readonly class TokenVerificationResultDto
{
    public function __construct(
        public bool $success,
        public bool $valid,
        public ?array $admin = null,
        public ?string $error = null
    ) {
    }

    public static function valid(array $admin): self
    {
        return new self(
            success: true,
            valid: true,
            admin: $admin
        );
    }

    public static function invalid(string $error = 'Token is invalid'): self
    {
        return new self(
            success: true,
            valid: false,
            error: $error
        );
    }

    public static function failure(string $error): self
    {
        return new self(
            success: false,
            valid: false,
            error: $error
        );
    }

    public function isValid(): bool
    {
        return $this->success && $this->valid;
    }

    public function isFailure(): bool
    {
        return ! $this->success;
    }
}
