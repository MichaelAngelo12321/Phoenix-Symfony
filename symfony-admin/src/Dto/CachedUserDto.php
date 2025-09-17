<?php

declare(strict_types=1);

namespace App\Dto;

use App\Enum\GenderEnum;

final readonly class CachedUserDto
{
    public function __construct(
        public ?int $id = null,
        public ?string $firstName = null,
        public ?string $lastName = null,
        public ?\DateTimeInterface $birthdate = null,
        public ?GenderEnum $gender = null,
        public int $cacheTimestamp = 0
    ) {
    }

    public static function fromApiResponse(array $apiData): self
    {
        return new self(
            id: $apiData['id'] ?? null,
            firstName: $apiData['first_name'] ?? null,
            lastName: $apiData['last_name'] ?? null,
            birthdate: isset($apiData['birthdate'])
                ? new \DateTime($apiData['birthdate'])
                : null,
            gender: isset($apiData['gender'])
                ? GenderEnum::fromString($apiData['gender'])
                : null,
            cacheTimestamp: time()
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'first_name' => $this->firstName,
            'last_name' => $this->lastName,
            'birthdate' => $this->birthdate?->format('Y-m-d'),
            'gender' => $this->gender?->value,
            '_cache_timestamp' => $this->cacheTimestamp,
        ];
    }

    public function getFullName(): string
    {
        return trim(($this->firstName ?? '') . ' ' . ($this->lastName ?? ''));
    }

    public function matchesFilter(string $filterType, mixed $filterValue): bool
    {
        return match ($filterType) {
            'firstName' => $this->firstName && str_contains(
                strtolower($this->firstName),
                strtolower((string) $filterValue)
            ),
            'lastName' => $this->lastName && str_contains(
                strtolower($this->lastName),
                strtolower((string) $filterValue)
            ),
            'gender' => $this->gender?->value === $filterValue,
            'birthdateFrom' => $this->birthdate && $this->birthdate >= $filterValue,
            'birthdateTo' => $this->birthdate && $this->birthdate <= $filterValue,
            default => false
        };
    }

    public function getSortValue(string $sortBy): mixed
    {
        return match ($sortBy) {
            'firstName' => $this->firstName ?? '',
            'lastName' => $this->lastName ?? '',
            'birthdate' => $this->birthdate?->getTimestamp() ?? 0,
            'gender' => $this->gender?->value ?? '',
            'id' => $this->id ?? 0,
            default => ''
        };
    }
}
