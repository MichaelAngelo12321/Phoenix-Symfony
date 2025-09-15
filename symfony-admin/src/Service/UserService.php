<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\FilterDto;
use App\Dto\UserListResponseDto;
use App\Dto\UserRequestDto;
use App\Dto\UserResponseDto;
use App\Enum\UserMessage;
use App\Mapper\UserMapper;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;

final readonly class UserService implements UserServiceInterface
{
    public function __construct(
        private PhoenixApiServiceInterface $phoenixApiService,
        private LoggerInterface $logger,
        private ResponseFactoryInterface $responseFactory,
    ) {
    }

    public function getUsers(string $token, Request $request): UserListResponseDto
    {
        try {
            $filterDto = FilterDto::fromRequest($request);
            $filters = $filterDto->toArray();

            $apiResponse = $this->phoenixApiService->getUsers($token, $filters);
            $usersData = $apiResponse['data'] ?? [];

            $users = UserMapper::mapApiResponseArrayToUserDtos($usersData);

            return $this->responseFactory->createUserListSuccessResponse(
                $users,
                $filterDto->getCurrentFilters(),
                $filterDto->sortBy,
                $filterDto->sortOrder
            );
        } catch (\Exception $e) {
            return $this->handleApiError($e, UserMessage::API_FETCH_USERS_ERROR->value);
        }
    }

    public function getUser(string $token, int $id): UserResponseDto
    {
        try {
            $apiResponse = $this->phoenixApiService->getUser($token, $id);
            $userData = $apiResponse['data'] ?? null;

            if ($userData === null) {
                return $this->responseFactory->createUserFailureResponse(
                    [UserMessage::FETCH_FAILED->value],
                    false
                );
            }

            $user = UserMapper::mapApiResponseToUserDto($userData);

            if ($user === null) {
                return $this->responseFactory->createUserFailureResponse(
                    [UserMessage::MAPPING_ERROR->value],
                    false
                );
            }

            return $this->responseFactory->createUserSuccessResponse($user);
        } catch (\Exception $e) {
            return $this->handleApiError($e, UserMessage::API_FETCH_ERROR->value, ['user_id' => $id]);
        }
    }

    public function createUser(string $token, UserRequestDto $userRequest): UserResponseDto
    {
        return $this->processUserRequest(
            $userRequest,
            fn ($apiData) => $this->phoenixApiService->createUser($token, $apiData),
            UserMessage::USER_CREATED,
            UserMessage::CREATE_FAILED,
            UserMessage::MAPPING_ERROR,
            UserMessage::API_CREATE_ERROR
        );
    }

    public function updateUser(string $token, int $id, UserRequestDto $userRequest): UserResponseDto
    {
        return $this->processUserRequest(
            $userRequest,
            fn ($apiData) => $this->phoenixApiService->updateUser($token, $id, $apiData),
            UserMessage::USER_UPDATED,
            UserMessage::UPDATE_FAILED,
            UserMessage::MAPPING_ERROR,
            UserMessage::API_UPDATE_ERROR
        );
    }

    public function deleteUser(string $token, int $id): UserResponseDto
    {
        try {
            $this->phoenixApiService->deleteUser($token, $id);

            return UserResponseDto::successWithoutData(UserMessage::USER_DELETED->value);
        } catch (\Exception $e) {
            return $this->handleApiError($e, UserMessage::API_DELETE_ERROR->value, ['user_id' => $id]);
        }
    }

    public function importUsers(string $token): UserListResponseDto
    {
        try {
            $result = $this->phoenixApiService->importUsers($token);
            $usersData = $result['data'] ?? [];

            $users = UserMapper::mapApiResponseArrayToUserDtos($usersData);

            return $this->responseFactory->createUserListSuccessResponse(
                users: $users,
                currentFilters: [],
                sortBy: 'id',
                sortOrder: 'asc'
            );
        } catch (\Exception $e) {
            return $this->handleApiError($e, UserMessage::API_IMPORT_ERROR->value);
        }
    }

    /**
     * @return array<string> Array of validation errors
     */
    private function validateUserRequest(UserRequestDto $userRequest): array
    {
        $errors = [];

        if (! $userRequest->isValid()) {
            $errors[] = UserMessage::VALIDATION_ERROR->value;
        }

        $validationErrors = $userRequest->getValidationErrors();
        if (! empty($validationErrors)) {
            $errors = array_merge($errors, array_values($validationErrors));
        }

        return $errors;
    }

    private function handleApiError(\Exception $e, string $logMessage, array $context = []): UserResponseDto|UserListResponseDto
    {
        $this->logger->error($logMessage, array_merge([
            'error' => $e->getMessage(),
        ], $context));

        $errors = UserMapper::mapApiErrorToErrors($e);

        return str_contains($logMessage, 'users') && ! str_contains($logMessage, 'user via')
            ? $this->responseFactory->createUserListFailureResponse($errors, false)
            : $this->responseFactory->createUserFailureResponse($errors, false);
    }

    private function processUserRequest(
        UserRequestDto $userRequest,
        callable $apiCall,
        UserMessage $successMessage,
        UserMessage $apiErrorMessage,
        UserMessage $mappingErrorMessage,
        UserMessage $logMessage
    ): UserResponseDto {
        try {
            $validationErrors = $this->validateUserRequest($userRequest);
            if (! empty($validationErrors)) {
                return $this->responseFactory->createUserFailureResponse($validationErrors, true);
            }

            $apiData = UserMapper::mapUserRequestDtoToApiRequest($userRequest);
            $apiResponse = $apiCall($apiData);
            $userData = $apiResponse['data'] ?? null;

            if ($userData === null) {
                return $this->responseFactory->createUserFailureResponse([$apiErrorMessage->value], true);
            }

            $user = UserMapper::mapApiResponseToUserDto($userData);
            if ($user === null) {
                return $this->responseFactory->createUserFailureResponse([$mappingErrorMessage->value], true);
            }

            return $this->responseFactory->createUserSuccessResponse($user, $successMessage->value);
        } catch (\Exception $e) {
            return $this->handleApiError($e, $logMessage->value, ['user_data' => $userRequest->toArray()]);
        }
    }
}
