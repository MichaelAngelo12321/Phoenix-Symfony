<?php

declare(strict_types=1);

namespace App\Security;

use Symfony\Component\Security\Core\User\UserInterface;

final readonly class TokenUser implements UserInterface
{
    public function __construct(
        private string $token
    ) {
    }

    public function getRoles(): array
    {
        return ['ROLE_ADMIN'];
    }

    public function eraseCredentials(): void
    {
    }

    public function getUserIdentifier(): string
    {
        return $this->token;
    }

    public function getToken(): string
    {
        return $this->token;
    }
}
