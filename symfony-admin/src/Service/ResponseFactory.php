<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\UserDto;
use App\Dto\UserListResponseDto;
use App\Dto\UserResponseDto;

final readonly class ResponseFactory implements ResponseFactoryInterface
{
    public function createUserSuccessResponse(UserDto $user, ?string $message = null): UserResponseDto
    {
        return UserResponseDto::success($user, $message);
    }

    public function createUserFailureResponse(array $errors, bool $apiAvailable = true): UserResponseDto
    {
        return UserResponseDto::failure($errors, $apiAvailable);
    }

    public function createUserListSuccessResponse(
        array $users,
        array $currentFilters = [],
        string $sortBy = 'id',
        string $sortOrder = 'asc'
    ): UserListResponseDto {
        return UserListResponseDto::success(
            $users,
            $currentFilters,
            $sortBy,
            $sortOrder
        );
    }

    public function createUserListFailureResponse(array $errors, bool $apiAvailable = true): UserListResponseDto
    {
        return UserListResponseDto::failure($errors, $apiAvailable);
    }
}
