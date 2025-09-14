<?php

declare(strict_types=1);

namespace App\Dto;

use App\Enum\GenderEnum;

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
            firstName: $data['first_name'] ?? $data['firstName'] ?? null,
            lastName: $data['last_name'] ?? $data['lastName'] ?? null,
            birthdate: $data['birthdate'] ?? null,
            gender: GenderEnum::fromString($data['gender'] ?? null)
        );
    }

    public function toUserDto(?int $id = null): UserDto
    {
        return new UserDto(
            id: $id,
            firstName: $this->firstName,
            lastName: $this->lastName,
            birthdate: $this->birthdate,
            gender: $this->gender
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
        $errors = [];

        if (empty($this->firstName)) {
            $errors['firstName'] = 'Imię jest wymagane';
        } elseif (strlen($this->firstName) < 2) {
            $errors['firstName'] = 'Imię musi mieć co najmniej 2 znaki';
        }

        if (empty($this->lastName)) {
            $errors['lastName'] = 'Nazwisko jest wymagane';
        } elseif (strlen($this->lastName) < 2) {
            $errors['lastName'] = 'Nazwisko musi mieć co najmniej 2 znaki';
        }

        if ($this->birthdate === null) {
            $errors['birthdate'] = 'Data urodzenia jest wymagana';
        } elseif ($this->birthdate > new \DateTime()) {
            $errors['birthdate'] = 'Data urodzenia nie może być z przyszłości';
        }

        if ($this->gender === null) {
            $errors['gender'] = 'Płeć jest wymagana';
        }

        return $errors;
    }
}
