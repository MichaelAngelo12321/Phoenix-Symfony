<?php

declare(strict_types=1);

namespace App\Enum;

enum GenderEnum: string
{
    case MALE = 'male';
    case FEMALE = 'female';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::MALE => 'Mężczyzna',
            self::FEMALE => 'Kobieta',
        };
    }

    public static function fromString(?string $value): ?self
    {
        return match ($value) {
            null, '' => null,
            '1' => self::MALE,
            '2' => self::FEMALE,
            default => self::tryFrom($value)
        };
    }
}
