<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\FilterDto;
use App\Dto\UserListResponseDto;
use App\Dto\UserRequestDto;
use App\Dto\UserResponseDto;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;

final readonly class UserService implements UserServiceInterface
{
    public function __construct(
        private PhoenixApiServiceInterface $phoenixApiService,
        private UserMapperServiceInterface $userMapperService,
        private LoggerInterface $logger,
    ) {
    }

    public function getUsers(string $token, Request $request): UserListResponseDto
    {
        try {
            $filterDto = FilterDto::fromRequest($request);
            $filters = $filterDto->toArray();

            $apiResponse = $this->phoenixApiService->getUsers($token, $filters);
            $usersData = $apiResponse['data'] ?? [];

            $users = $this->userMapperService->mapApiResponseArrayToUserDtos($usersData);

            return $this->userMapperService->createUserListSuccessResponse(
                $users,
                $filterDto->getCurrentFilters(),
                $filterDto->sortBy,
                $filterDto->sortOrder
            );
        } catch (\Exception $e) {
            $this->logger->error('Failed to fetch users for admin panel', [
                'error' => $e->getMessage(),
            ]);

            $errors = $this->userMapperService->mapApiErrorToErrors($e);
            return $this->userMapperService->createUserListFailureResponse($errors, false);
        }
    }

    public function getUser(string $token, int $id): UserResponseDto
    {
        try {
            $apiResponse = $this->phoenixApiService->getUser($token, $id);
            $userData = $apiResponse['data'] ?? null;

            if ($userData === null) {
                return $this->userMapperService->createFailureResponse(
                    ['Użytkownik nie został znaleziony'],
                    true
                );
            }

            $user = $this->userMapperService->mapApiResponseToUserDto($userData);

            if ($user === null) {
                return $this->userMapperService->createFailureResponse(
                    ['Błąd mapowania danych użytkownika'],
                    true
                );
            }

            return $this->userMapperService->createSuccessResponse($user);
        } catch (\Exception $e) {
            $this->logger->error('Failed to fetch user from Phoenix API', [
                'user_id' => $id,
                'error' => $e->getMessage(),
            ]);

            $errors = $this->userMapperService->mapApiErrorToErrors($e);
            return $this->userMapperService->createFailureResponse($errors, false);
        }
    }

    public function createUser(string $token, UserRequestDto $userRequest): UserResponseDto
    {
        try {
            $validationErrors = $this->validateUserRequest($userRequest);
            if (! empty($validationErrors)) {
                return $this->userMapperService->createFailureResponse($validationErrors, true);
            }

            $apiData = $this->userMapperService->mapUserRequestDtoToApiRequest($userRequest);

            $apiResponse = $this->phoenixApiService->createUser($token, $apiData);
            $userData = $apiResponse['data'] ?? null;

            if ($userData === null) {
                return $this->userMapperService->createFailureResponse(
                    ['Błąd podczas tworzenia użytkownika'],
                    true
                );
            }

            $user = $this->userMapperService->mapApiResponseToUserDto($userData);

            if ($user === null) {
                return $this->userMapperService->createFailureResponse(
                    ['Błąd mapowania utworzonego użytkownika'],
                    true
                );
            }

            return $this->userMapperService->createSuccessResponse(
                $user,
                'Użytkownik został pomyślnie utworzony'
            );
        } catch (\Exception $e) {
            $this->logger->error('Failed to create user via Phoenix API', [
                'user_data' => $userRequest->toArray(),
                'error' => $e->getMessage(),
            ]);

            $errors = $this->userMapperService->mapApiErrorToErrors($e);
            return $this->userMapperService->createFailureResponse($errors, false);
        }
    }

    public function updateUser(string $token, int $id, UserRequestDto $userRequest): UserResponseDto
    {
        try {
            $validationErrors = $this->validateUserRequest($userRequest);
            if (! empty($validationErrors)) {
                return $this->userMapperService->createFailureResponse($validationErrors, true);
            }

            $apiData = $this->userMapperService->mapUserRequestDtoToApiRequest($userRequest);

            $apiResponse = $this->phoenixApiService->updateUser($token, $id, $apiData);
            $userData = $apiResponse['data'] ?? null;

            if ($userData === null) {
                return $this->userMapperService->createFailureResponse(
                    ['Błąd podczas aktualizacji użytkownika'],
                    true
                );
            }

            $user = $this->userMapperService->mapApiResponseToUserDto($userData);

            if ($user === null) {
                return $this->userMapperService->createFailureResponse(
                    ['Błąd mapowania zaktualizowanego użytkownika'],
                    true
                );
            }

            return $this->userMapperService->createSuccessResponse(
                $user,
                'Użytkownik został pomyślnie zaktualizowany'
            );
        } catch (\Exception $e) {
            $this->logger->error('Failed to update user via Phoenix API', [
                'user_id' => $id,
                'user_data' => $userRequest->toArray(),
                'error' => $e->getMessage(),
            ]);

            $errors = $this->userMapperService->mapApiErrorToErrors($e);
            return $this->userMapperService->createFailureResponse($errors, false);
        }
    }

    public function deleteUser(string $token, int $id): UserResponseDto
    {
        try {
            $this->phoenixApiService->deleteUser($token, $id);

            return UserResponseDto::successWithoutData('Użytkownik został pomyślnie usunięty');
        } catch (\Exception $e) {
            $this->logger->error('Failed to delete user via Phoenix API', [
                'user_id' => $id,
                'error' => $e->getMessage(),
            ]);

            $errors = $this->userMapperService->mapApiErrorToErrors($e);
            return $this->userMapperService->createFailureResponse($errors, false);
        }
    }

    public function importUsers(string $token): UserListResponseDto
    {
        try {
            $result = $this->phoenixApiService->importUsers($token);
            $usersData = $result['data'] ?? [];

            $users = $this->userMapperService->mapApiResponseArrayToUserDtos($usersData);

            return $this->userMapperService->createUserListSuccessResponse(
                users: $users,
                currentFilters: [],
                sortBy: 'id',
                sortOrder: 'asc'
            );
        } catch (\Exception $e) {
            $this->logger->error('Failed to import users', [
                'error' => $e->getMessage(),
            ]);

            $errors = $this->userMapperService->mapApiErrorToErrors($e);
            return $this->userMapperService->createUserListFailureResponse($errors, false);
        }
    }

    /**
     * @return array<string> Array of validation errors
     */
    private function validateUserRequest(UserRequestDto $userRequest): array
    {
        $errors = [];

        if (! $userRequest->isValid()) {
            $errors[] = 'Wszystkie wymagane pola muszą być wypełnione';
        }

        $validationErrors = $userRequest->getValidationErrors();
        if (! empty($validationErrors)) {
            $errors = array_merge($errors, array_values($validationErrors));
        }

        return $errors;
    }
}
