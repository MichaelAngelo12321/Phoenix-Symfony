<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\UserDto;
use App\Dto\UserListResponseDto;
use App\Dto\UserRequestDto;
use App\Dto\UserResponseDto;

/**
 * User Mapper Service Interface
 *
 * Defines contract for user data mapping operations
 */
interface UserMapperServiceInterface
{
    /**
     * Map API response array to UserDto
     */
    public function mapApiResponseToUserDto(array $apiData): ?UserDto;

    /**
     * Map array of API responses to UserDto array
     */
    public function mapApiResponseArrayToUserDtos(array $apiDataArray): array;

    /**
     * Map form data array to UserRequestDto
     */
    public function mapFormDataToUserRequestDto(array $formData): UserRequestDto;

    /**
     * Map UserDto to API request format
     */
    public function mapUserDtoToApiRequest(UserDto $userDto): array;

    /**
     * Map UserRequestDto to API request format
     */
    public function mapUserRequestDtoToApiRequest(UserRequestDto $requestDto): array;

    /**
     * Create UserResponseDto from successful operation
     */
    public function createSuccessResponse(UserDto $user, ?string $message = null): UserResponseDto;

    /**
     * Create UserResponseDto from failed operation
     */
    public function createFailureResponse(array $errors, bool $apiAvailable = true): UserResponseDto;

    /**
     * Create UserListResponseDto from successful operation
     */
    public function createUserListSuccessResponse(
        array $users,
        array $currentFilters = [],
        string $sortBy = 'id',
        string $sortOrder = 'asc'
    ): UserListResponseDto;

    /**
     * Create UserListResponseDto from failed operation
     */
    public function createUserListFailureResponse(array $errors, bool $apiAvailable = true): UserListResponseDto;

    /**
     * Map API error response to error array
     */
    public function mapApiErrorToErrors(\Exception $exception): array;
}
