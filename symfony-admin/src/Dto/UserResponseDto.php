<?php

declare(strict_types=1);

namespace App\Dto;

final readonly class UserResponseDto
{
    public function __construct(
        public bool $success,
        public ?UserDto $user = null,
        public array $errors = [],
        public bool $apiAvailable = true,
        public ?string $message = null
    ) {
    }

    public static function success(UserDto $user, ?string $message = null): self
    {
        return new self(
            success: true,
            user: $user,
            message: $message
        );
    }

    public static function successWithoutData(?string $message = null): self
    {
        return new self(
            success: true,
            message: $message
        );
    }

    public static function failure(array $errors, bool $apiAvailable = true): self
    {
        return new self(
            success: false,
            errors: $errors,
            apiAvailable: $apiAvailable
        );
    }

    public static function apiUnavailable(string $error = 'API jest niedostępne'): self
    {
        return new self(
            success: false,
            errors: [$error],
            apiAvailable: false
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

    public function getFirstError(): ?string
    {
        return $this->errors[0] ?? null;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function isApiAvailable(): bool
    {
        return $this->apiAvailable;
    }

    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'user' => $this->user?->toArray(),
            'errors' => $this->errors,
            'api_available' => $this->apiAvailable,
            'message' => $this->message,
        ];
    }
}
