<?php

declare(strict_types=1);

namespace App\Service;

/**
 * Interface for Phoenix Authentication Service
 *
 * Defines contract for authentication operations with Phoenix backend API.
 */
interface PhoenixAuthServiceInterface
{
    /**
     * Authenticate admin with Phoenix API
     *
     * @return array<string, mixed>
     */
    public function login(string $email, string $password): array;

    /**
     * Verify JWT token with Phoenix API
     *
     * @return array<string, mixed>
     */
    public function verifyToken(string $token): array;

    /**
     * Make authenticated request to Phoenix API
     *
     * @param array<string, mixed> $options
     *
     * @return array<string, mixed>
     */
    public function makeAuthenticatedRequest(string $method, string $endpoint, string $token, array $options = []): array;
}
