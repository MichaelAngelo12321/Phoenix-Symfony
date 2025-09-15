<?php

declare(strict_types=1);

namespace App\Mapper;

use App\Dto\UserDto;
use App\Dto\UserRequestDto;
use App\Enum\GenderEnum;

final readonly class UserMapper
{
    public static function mapApiResponseToUserDto(array $apiData): ?UserDto
    {
        if (empty($apiData)) {
            return null;
        }

        return new UserDto(
            id: $apiData['id'] ?? null,
            firstName: $apiData['first_name'] ?? null,
            lastName: $apiData['last_name'] ?? null,
            birthdate: isset($apiData['birthdate']) ? self::parseDate($apiData['birthdate']) : null,
            gender: isset($apiData['gender']) ? GenderEnum::fromString($apiData['gender']) : null,
        );
    }

    public static function mapApiResponseArrayToUserDtos(array $apiDataArray): array
    {
        $users = [];

        foreach ($apiDataArray as $apiData) {
            if (! is_array($apiData)) {
                continue;
            }

            $userDto = self::mapApiResponseToUserDto($apiData);
            if ($userDto !== null) {
                $users[] = $userDto;
            }
        }

        return $users;
    }

    public static function mapFormDataToUserRequestDto(array $formData): UserRequestDto
    {
        return new UserRequestDto(
            firstName: $formData['first_name'] ?? null,
            lastName: $formData['last_name'] ?? null,
            birthdate: $formData['birthdate'] ?? null,
            gender: isset($formData['gender']) ? GenderEnum::fromString($formData['gender']) : null
        );
    }

    public static function mapUserDtoToApiRequest(UserDto $userDto): array
    {
        return self::mapUserFieldsToApiData(
            $userDto->firstName,
            $userDto->lastName,
            $userDto->birthdate,
            $userDto->gender
        );
    }

    public static function mapUserRequestDtoToApiRequest(UserRequestDto $requestDto): array
    {
        return self::mapUserFieldsToApiData(
            $requestDto->firstName,
            $requestDto->lastName,
            $requestDto->birthdate,
            $requestDto->gender
        );
    }

    public static function mapApiErrorToErrors(\Exception $exception): array
    {
        $message = $exception->getMessage();

        if (str_contains($message, 'Validation error:')) {
            $jsonPart = str_replace('Validation error: ', '', $message);
            $validationErrors = json_decode($jsonPart, true);

            if (is_array($validationErrors)) {
                return self::flattenValidationErrors($validationErrors);
            }
        }

        return [$message];
    }

    private static function parseDate(string $dateString): ?\DateTimeInterface
    {
        try {
            return new \DateTime($dateString);
        } catch (\Exception) {
            return null;
        }
    }

    private static function flattenValidationErrors(array $errors): array
    {
        $flattened = [];

        foreach ($errors as $field => $fieldErrors) {
            if (is_array($fieldErrors)) {
                foreach ($fieldErrors as $error) {
                    $flattened[] = "{$field}: {$error}";
                }
            } else {
                $flattened[] = "{$field}: {$fieldErrors}";
            }
        }

        return $flattened;
    }

    private static function mapUserFieldsToApiData(
        ?string $firstName,
        ?string $lastName,
        ?\DateTimeInterface $birthdate,
        ?GenderEnum $gender
    ): array {
        $data = [];

        if ($firstName !== null) {
            $data['first_name'] = strtoupper($firstName);
        }

        if ($lastName !== null) {
            $data['last_name'] = strtoupper($lastName);
        }

        if ($birthdate !== null) {
            $data['birthdate'] = $birthdate->format('Y-m-d');
        }

        if ($gender !== null) {
            $data['gender'] = $gender->value;
        }

        return $data;
    }
}
