<?php

declare(strict_types=1);

namespace App\Dto;

use App\Enum\GenderEnum;

final readonly class UserDto
{
    public function __construct(
        public ?int $id = null,
        public ?string $firstName = null,
        public ?string $lastName = null,
        public ?\DateTimeInterface $birthdate = null,
        public ?GenderEnum $gender = null
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'] ?? null,
            firstName: $data['first_name'] ?? null,
            lastName: $data['last_name'] ?? null,
            birthdate: isset($data['birthdate']) ? new \DateTime($data['birthdate']) : null,
            gender: GenderEnum::fromString($data['gender'] ?? null)
        );
    }

    /**
     * Convert UserDto to array (e.g., for API requests)
     */
    public function toArray(): array
    {
        return array_filter([
            'id' => $this->id,
            'first_name' => $this->firstName,
            'last_name' => $this->lastName,
            'birthdate' => $this->birthdate?->format('Y-m-d'),
            'gender' => $this->gender?->value,
        ], static fn ($value) => $value !== null);
    }

    public function toFormArray(): array
    {
        return array_filter([
            'id' => $this->id,
            'first_name' => $this->firstName,
            'last_name' => $this->lastName,
            'birthdate' => $this->birthdate,
            'gender' => $this->gender?->value,
        ], static fn ($value) => $value !== null);
    }
}
