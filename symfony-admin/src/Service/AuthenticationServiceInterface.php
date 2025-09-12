<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Interface for authentication service operations
 */
interface AuthenticationServiceInterface
{
    /**
     * Get JWT token from session or return redirect response
     *
     * @return string|RedirectResponse JWT token string or redirect Response
     */
    public function getTokenOrRedirect(): string|RedirectResponse;

    /**
     * Check if user is authenticated
     */
    public function isAuthenticated(): bool;

    /**
     * Get current token without redirect
     */
    public function getCurrentToken(): ?string;
}
