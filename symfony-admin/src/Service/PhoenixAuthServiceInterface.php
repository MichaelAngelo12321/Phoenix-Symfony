<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\AuthenticationResultDto;
use App\Dto\TokenVerificationResultDto;

/**
 * Interface for Phoenix Authentication Service
 *
 * Defines contract for authentication operations with Phoenix backend API.
 */
interface PhoenixAuthServiceInterface
{
    /**
     * Authenticate admin with Phoenix API
     */
    public function login(string $email, string $password): AuthenticationResultDto;

    /**
     * Verify JWT token with Phoenix API
     */
    public function verifyToken(string $token): TokenVerificationResultDto;

    /**
     * Make authenticated request to Phoenix API
     *
     * @param array<string, mixed> $options
     *
     * @return array<string, mixed>
     */
    public function makeAuthenticatedRequest(string $method, string $endpoint, string $token, array $options = []): array;
}
