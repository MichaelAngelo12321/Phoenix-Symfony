<?php

declare(strict_types=1);

namespace App\Factory;

use App\Dto\UserDto;
use App\Dto\UserListResponseDto;
use App\Dto\UserResponseDto;

interface ResponseFactoryInterface
{
    public function createUserSuccessResponse(UserDto $user, ?string $message = null): UserResponseDto;

    public function createUserFailureResponse(array $errors, bool $apiAvailable = true): UserResponseDto;

    public function createUserListSuccessResponse(
        array $users,
        array $currentFilters = [],
        string $sortBy = 'id',
        string $sortOrder = 'asc'
    ): UserListResponseDto;

    public function createUserListFailureResponse(array $errors, bool $apiAvailable = true): UserListResponseDto;
}
