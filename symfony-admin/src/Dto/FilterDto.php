<?php

declare(strict_types=1);

namespace App\Dto;

use App\Enum\GenderEnum;
use Symfony\Component\HttpFoundation\Request;

final readonly class FilterDto
{
    public function __construct(
        public ?string $firstName = null,
        public ?string $lastName = null,
        public ?GenderEnum $gender = null,
        public ?\DateTimeInterface $birthdateFrom = null,
        public ?\DateTimeInterface $birthdateTo = null,
        public string $sortBy = 'id',
        public string $sortOrder = 'asc'
    ) {
    }

    public static function fromRequest(Request $request): self
    {
        $birthdateFrom = null;
        $birthdateTo = null;

        if ($request->query->get('birthdateFrom')) {
            try {
                $birthdateFrom = new \DateTime($request->query->get('birthdateFrom'));
            } catch (\Exception) {
            }
        }

        if ($request->query->get('birthdateTo')) {
            try {
                $birthdateTo = new \DateTime($request->query->get('birthdateTo'));
            } catch (\Exception) {
            }
        }

        return new self(
            firstName: $request->query->get('firstName') ?: null,
            lastName: $request->query->get('lastName') ?: null,
            gender: GenderEnum::fromString($request->query->get('gender')),
            birthdateFrom: $birthdateFrom,
            birthdateTo: $birthdateTo,
            sortBy: self::validateSortBy($request->query->get('sortBy', 'id')),
            sortOrder: self::validateSortOrder($request->query->get('sortOrder', 'asc'))
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'first_name' => $this->firstName,
            'last_name' => $this->lastName,
            'gender' => $this->gender?->value,
            'birthdate_from' => $this->birthdateFrom?->format('Y-m-d'),
            'birthdate_to' => $this->birthdateTo?->format('Y-m-d'),
            'sort_by' => $this->sortBy,
            'sort_order' => $this->sortOrder,
        ], static fn ($value) => $value !== null && $value !== '');
    }

    public function hasFilters(): bool
    {
        return $this->firstName !== null
            || $this->lastName !== null
            || $this->gender !== null
            || $this->birthdateFrom !== null
            || $this->birthdateTo !== null;
    }

    public function getCurrentFilters(): array
    {
        return [
            'first_name' => $this->firstName,
            'last_name' => $this->lastName,
            'gender' => $this->gender?->value,
            'birthdate_from' => $this->birthdateFrom?->format('Y-m-d'),
            'birthdate_to' => $this->birthdateTo?->format('Y-m-d'),
        ];
    }

    private static function validateSortBy(string $sortBy): string
    {
        $allowedFields = ['id', 'first_name', 'last_name', 'birthdate', 'gender', 'created_at'];

        return in_array($sortBy, $allowedFields, true) ? $sortBy : 'id';
    }

    private static function validateSortOrder(string $sortOrder): string
    {
        return in_array(strtolower($sortOrder), ['asc', 'desc'], true) ? strtolower($sortOrder) : 'asc';
    }
}
