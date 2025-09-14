<?php

declare(strict_types=1);

namespace App\Dto;

final readonly class UserListResponseDto
{
    public function __construct(
        public bool $success,
        /** @var array<UserDto> */
        public array $users = [],
        public array $errors = [],
        public bool $apiAvailable = true,
        public array $currentFilters = [],
        public string $sortBy = 'id',
        public string $sortOrder = 'asc'
    ) {
    }

    public static function success(
        array $users,
        array $currentFilters = [],
        string $sortBy = 'id',
        string $sortOrder = 'asc'
    ): self {
        return new self(
            success: true,
            users: $users,
            currentFilters: $currentFilters,
            sortBy: $sortBy,
            sortOrder: $sortOrder
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
            'users' => array_map(static fn (UserDto $user) => $user->toArray(), $this->users),
            'errors' => $this->errors,
            'api_available' => $this->apiAvailable,
            'current_filters' => $this->currentFilters,
            'sort_by' => $this->sortBy,
            'sort_order' => $this->sortOrder,
        ];
    }
}
