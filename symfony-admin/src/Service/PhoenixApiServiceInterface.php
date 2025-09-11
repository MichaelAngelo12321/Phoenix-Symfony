<?php

declare(strict_types=1);

namespace App\Service;

/**
 * Interface for Phoenix API Service
 *
 * Defines contract for communicating with Phoenix backend API,
 * including user management operations.
 */
interface PhoenixApiServiceInterface
{
    /**
     * Get all users from Phoenix API with optional filtering and sorting
     *
     * @param string $token JWT token for authentication
     * @param array<string, mixed> $params Query parameters for filtering and sorting
     *
     * @return array<string, mixed>
     *
     * @throws \Exception
     */
    public function getUsers(string $token, array $params = []): array;

    /**
     * Get a specific user by ID from Phoenix API
     *
     * @param string $token JWT token for authentication
     *
     * @return array<string, mixed>
     *
     * @throws \Exception
     */
    public function getUser(string $token, int $id): array;

    /**
     * Create a new user via Phoenix API
     *
     * @param string $token JWT token for authentication
     * @param array<string, mixed> $userData
     *
     * @return array<string, mixed>
     *
     * @throws \Exception
     */
    public function createUser(string $token, array $userData): array;

    /**
     * Update an existing user via Phoenix API
     *
     * @param string $token JWT token for authentication
     * @param array<string, mixed> $userData
     *
     * @return array<string, mixed>
     *
     * @throws \Exception
     */
    public function updateUser(string $token, int $id, array $userData): array;

    /**
     * Delete a user via Phoenix API
     *
     * @param string $token JWT token for authentication
     *
     * @throws \Exception
     */
    public function deleteUser(string $token, int $id): bool;

    /**
     * Import users from external API (dane.gov.pl)
     *
     * @param string $token JWT token for authentication
     *
     * @return array<string, mixed>
     *
     * @throws \Exception
     */
    public function importUsers(string $token): array;

    /**
     * Check if Phoenix API is available
     */
    public function isApiAvailable(): bool;
}
