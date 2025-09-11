<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Component\HttpFoundation\Request;

/**
 * User service interface
 *
 * Defines contract for user business logic operations
 */
interface UserServiceInterface
{
    /**
     * Get users with filtering and sorting
     *
     * @param string $token JWT token for API authentication
     * @param Request $request HTTP request with filter parameters
     *
     * @return array{users: array, api_available: bool, current_filters: array, sort_by: string, sort_order: string, errors: array}
     */
    public function getUsers(string $token, Request $request): array;

    /**
     * Get single user by ID
     *
     * @return array{user: array|null, api_available: bool, errors: array}
     */
    public function getUser(string $token, int $id): array;

    /**
     * Create new user
     *
     * @param string $token JWT token for API authentication
     * @param array<string, mixed> $userData User data to create
     *
     * @return array{success: bool, user: array|null, errors: array}
     */
    public function createUser(string $token, array $userData): array;

    /**
     * Update existing user
     *
     * @param string $token JWT token for API authentication
     * @param int $id User ID
     * @param array<string, mixed> $userData User data to update
     *
     * @return array{success: bool, user: array|null, errors: array}
     */
    public function updateUser(string $token, int $id, array $userData): array;

    /**
     * Delete user
     *
     * @param string $token JWT token for API authentication
     * @param int $id User ID
     *
     * @return array{success: bool, errors: array}
     */
    public function deleteUser(string $token, int $id): array;

    /**
     * Import users from external source
     */
    public function importUsers(string $token): array;

    /**
     * Check API status
     */
    public function checkApiStatus(): array;
}
