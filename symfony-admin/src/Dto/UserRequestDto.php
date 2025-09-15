<?php

declare(strict_types=1);

namespace App\Dto;

use App\Enum\GenderEnum;
use App\Enum\ValidatorMessage;

final readonly class UserRequestDto
{
    public function __construct(
        public ?string $firstName = null,
        public ?string $lastName = null,
        public ?\DateTimeInterface $birthdate = null,
        public ?GenderEnum $gender = null
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            firstName: $data['first_name'] ?? null,
            lastName: $data['last_name'] ?? null,
            birthdate: $data['birthdate'] ?? null,
            gender: GenderEnum::fromString($data['gender'] ?? null)
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'first_name' => $this->firstName,
            'last_name' => $this->lastName,
            'birthdate' => $this->birthdate?->format('Y-m-d'),
            'gender' => $this->gender?->value,
        ], static fn ($value) => $value !== null);
    }

    public function isValid(): bool
    {
        return ! empty($this->firstName)
            && ! empty($this->lastName)
            && $this->birthdate !== null
            && $this->gender !== null;
    }

    public function getValidationErrors(): array
    {
        return array_filter([
            'firstName' => $this->validateFirstName(),
            'lastName' => $this->validateLastName(),
            'birthdate' => $this->validateBirthdate(),
            'gender' => $this->validateGender(),
        ]);
    }

    private function validateFirstName(): ?string
    {
        if (empty($this->firstName)) {
            return ValidatorMessage::FIRST_NAME_REQUIRED->value;
        }

        if (strlen($this->firstName) < 2) {
            return ValidatorMessage::FIRST_NAME_TOO_SHORT->value;
        }

        return null;
    }

    private function validateLastName(): ?string
    {
        if (empty($this->lastName)) {
            return ValidatorMessage::LAST_NAME_REQUIRED->value;
        }

        if (strlen($this->lastName) < 2) {
            return ValidatorMessage::LAST_NAME_TOO_SHORT->value;
        }

        return null;
    }

    private function validateBirthdate(): ?string
    {
        if (empty($this->birthdate)) {
            return ValidatorMessage::BIRTHDATE_REQUIRED->value;
        }

        if ($this->birthdate > new \DateTime()) {
            return ValidatorMessage::BIRTHDATE_FUTURE->value;
        }

        return null;
    }

    private function validateGender(): ?string
    {
        return $this->gender === null ? ValidatorMessage::GENDER_REQUIRED->value : null;
    }
}
