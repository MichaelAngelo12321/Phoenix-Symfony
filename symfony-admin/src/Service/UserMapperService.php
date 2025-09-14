<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\UserDto;
use App\Dto\UserListResponseDto;
use App\Dto\UserRequestDto;
use App\Dto\UserResponseDto;
use App\Enum\GenderEnum;
use Psr\Log\LoggerInterface;

final readonly class UserMapperService implements UserMapperServiceInterface
{
    public function __construct(
        private LoggerInterface $logger,
    ) {
    }

    public function mapApiResponseToUserDto(array $apiData): ?UserDto
    {
        try {
            if (empty($apiData)) {
                return null;
            }

            return new UserDto(
                id: $apiData['id'] ?? null,
                firstName: $apiData['first_name'] ?? null,
                lastName: $apiData['last_name'] ?? null,
                birthdate: isset($apiData['birthdate']) ? $this->parseDate($apiData['birthdate']) : null,
                gender: isset($apiData['gender']) ? GenderEnum::fromString($apiData['gender']) : null,
            );
        } catch (\Exception $e) {
            $this->logger->error('Failed to map API response to UserDto', [
                'api_data' => $apiData,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    public function mapApiResponseArrayToUserDtos(array $apiDataArray): array
    {
        $users = [];

        foreach ($apiDataArray as $index => $apiData) {
            if (!is_array($apiData)) {
                $this->logger->warning('Invalid data type in user array', [
                    'index' => $index,
                    'expected' => 'array',
                    'actual' => gettype($apiData),
                    'value' => $apiData
                ]);
                continue;
            }
            
            $userDto = $this->mapApiResponseToUserDto($apiData);
            if ($userDto !== null) {
                $users[] = $userDto;
            }
        }

        return $users;
    }

    /**
     * Map form data array to UserRequestDto
     */
    public function mapFormDataToUserRequestDto(array $formData): UserRequestDto
    {
        return new UserRequestDto(
            firstName: $formData['first_name'] ?? null,
            lastName: $formData['last_name'] ?? null,
            birthdate: $formData['birthdate'] ?? null,
            gender: isset($formData['gender']) ? GenderEnum::fromString($formData['gender']) : null
        );
    }

    public function mapUserDtoToApiRequest(UserDto $userDto): array
    {
        $data = [];

        if ($userDto->firstName !== null) {
            $data['first_name'] = strtoupper($userDto->firstName);
        }

        if ($userDto->lastName !== null) {
            $data['last_name'] = strtoupper($userDto->lastName);
        }

        if ($userDto->birthdate !== null) {
            $data['birthdate'] = $userDto->birthdate->format('Y-m-d');
        }

        if ($userDto->gender !== null) {
            $data['gender'] = $userDto->gender->value;
        }

        return $data;
    }

    public function mapUserRequestDtoToApiRequest(UserRequestDto $requestDto): array
    {
        $data = [];

        if ($requestDto->firstName !== null) {
            $data['first_name'] = strtoupper($requestDto->firstName);
        }

        if ($requestDto->lastName !== null) {
            $data['last_name'] = strtoupper($requestDto->lastName);
        }

        if ($requestDto->birthdate !== null) {
            $data['birthdate'] = $requestDto->birthdate->format('Y-m-d');
        }

        if ($requestDto->gender !== null) {
            $data['gender'] = $requestDto->gender->value;
        }

        return $data;
    }

    public function createSuccessResponse(UserDto $user, ?string $message = null): UserResponseDto
    {
        return UserResponseDto::success($user, $message);
    }

    public function createFailureResponse(array $errors, bool $apiAvailable = true): UserResponseDto
    {
        return UserResponseDto::failure($errors, $apiAvailable);
    }

    public function createUserListSuccessResponse(
        array $users,
        array $currentFilters = [],
        string $sortBy = 'id',
        string $sortOrder = 'asc'
    ): UserListResponseDto {
        return UserListResponseDto::success(
            $users,
            $currentFilters,
            $sortBy,
            $sortOrder
        );
    }

    public function createUserListFailureResponse(array $errors, bool $apiAvailable = true): UserListResponseDto
    {
        return UserListResponseDto::failure($errors, $apiAvailable);
    }

    public function mapApiErrorToErrors(\Exception $exception): array
    {
        $message = $exception->getMessage();

        if (str_contains($message, 'Validation error:')) {
            $jsonPart = str_replace('Validation error: ', '', $message);
            $validationErrors = json_decode($jsonPart, true);

            if (is_array($validationErrors)) {
                return $this->flattenValidationErrors($validationErrors);
            }
        }

        return [$message];
    }

    private function parseDate(string $dateString): ?\DateTimeInterface
    {
        try {
            return new \DateTime($dateString);
        } catch (\Exception $e) {
            $this->logger->warning('Failed to parse date string', [
                'date_string' => $dateString,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    private function flattenValidationErrors(array $errors): array
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
}
