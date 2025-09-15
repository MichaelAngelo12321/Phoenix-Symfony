<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\FilterDto;
use App\Dto\UserListResponseDto;
use App\Dto\UserRequestDto;
use App\Dto\UserResponseDto;

/**
 * User service interface
 *
 * Defines contract for user business logic operations
 * Uses DTOs for type safety and better structure
 */
interface UserServiceInterface
{
    /**
     * Get users with filtering and sorting
     */
    public function getUsers(string $token, FilterDto $filterDto): UserListResponseDto;

    /**
     * Get single user by ID
     */
    public function getUser(string $token, int $id): UserResponseDto;

    /**
     * Create new user
     */
    public function createUser(string $token, UserRequestDto $userRequest): UserResponseDto;

    /**
     * Update existing user
     */
    public function updateUser(string $token, int $id, UserRequestDto $userRequest): UserResponseDto;

    /**
     * Delete user
     */
    public function deleteUser(string $token, int $id): UserResponseDto;

    /**
     * Import users from external source
     */
    public function importUsers(string $token): UserListResponseDto;
}
