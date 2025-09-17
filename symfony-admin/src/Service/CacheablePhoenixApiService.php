<?php

declare(strict_types=1);

namespace App\Service;

use App\Exception\PhoenixApiException;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

final readonly class CacheablePhoenixApiService implements PhoenixApiServiceInterface
{
    private const int CACHE_TTL = 300;
    private const string USERS_CACHE_KEY = 'phoenix_api_users';
    private const string USER_CACHE_KEY_PREFIX = 'phoenix_api_user_';

    public function __construct(
        private PhoenixApiServiceInterface $phoenixApiService,
        private CacheInterface $cache,
        private LoggerInterface $logger,
    ) {
    }

    public function getUsers(string $token, array $params = []): array
    {
        try {
            $cacheKey = $this->generateUsersCacheKey($params);

            return $this->cache->get($cacheKey, function (ItemInterface $item) use ($token, $params) {
                $item->expiresAfter(self::CACHE_TTL);

                try {
                    $apiResponse = $this->phoenixApiService->getUsers($token, $params);

                    $this->logger->info('Users fetched from API and cached', [
                        'count' => count($apiResponse['data'] ?? []),
                        'params' => $params,
                    ]);

                    return $apiResponse;
                } catch (PhoenixApiException $e) {
                    $this->logger->warning('API failed, attempting fallback from cache', [
                        'error' => $e->getMessage(),
                    ]);

                    return $this->getFallbackUsers($params);
                }
            });
        } catch (\Exception $e) {
            $this->logger->error('Cache operation failed, using fallback', [
                'error' => $e->getMessage(),
            ]);

            return ['data' => []];
        }
    }

    public function getUser(string $token, int $id): array
    {
        try {
            $cacheKey = self::USER_CACHE_KEY_PREFIX . $id;

            return $this->cache->get($cacheKey, function (ItemInterface $item) use ($token, $id) {
                $item->expiresAfter(self::CACHE_TTL);

                try {
                    $apiResponse = $this->phoenixApiService->getUser($token, $id);

                    $this->logger->info('User fetched from API and cached', ['id' => $id]);

                    return $apiResponse;
                } catch (PhoenixApiException $e) {
                    $this->logger->warning('API failed for single user, attempting fallback', [
                        'id' => $id,
                        'error' => $e->getMessage(),
                    ]);

                    return $this->getFallbackUser($id);
                }
            });
        } catch (\Exception $e) {
            $this->logger->error('Cache operation failed for single user', [
                'id' => $id,
                'error' => $e->getMessage(),
            ]);

            return $this->getFallbackUser($id);
        }
    }

    public function createUser(string $token, array $userData): array
    {
        $createdUser = $this->phoenixApiService->createUser($token, $userData);

        $this->invalidateUsersCache();
        $this->cache->delete(self::USER_CACHE_KEY_PREFIX . ($createdUser['id'] ?? 0));

        $this->logger->info('User created, cache invalidated', ['id' => $createdUser['id'] ?? null]);

        return $createdUser;
    }

    public function updateUser(string $token, int $id, array $userData): array
    {
        $updatedUser = $this->phoenixApiService->updateUser($token, $id, $userData);

        $this->invalidateUsersCache();
        $this->cache->delete(self::USER_CACHE_KEY_PREFIX . $id);

        $this->logger->info('User updated, cache invalidated', ['id' => $id]);

        return $updatedUser;
    }

    public function deleteUser(string $token, int $id): bool
    {
        $result = $this->phoenixApiService->deleteUser($token, $id);

        $this->invalidateUsersCache();
        $this->cache->delete(self::USER_CACHE_KEY_PREFIX . $id);

        $this->logger->info('User deleted, cache invalidated', ['id' => $id]);

        return $result;
    }

    public function importUsers(string $token): array
    {
        $result = $this->phoenixApiService->importUsers($token);

        $this->invalidateUsersCache();

        $this->logger->info('Users imported, cache invalidated', [
            'imported_count' => count($result),
        ]);

        return $result;
    }

    public function isApiAvailable(): bool
    {
        return $this->phoenixApiService->isApiAvailable();
    }

    private function generateUsersCacheKey(array $params): string
    {
        return self::USERS_CACHE_KEY . '_' . md5(serialize($params));
    }

    private function getFallbackUsers(array $params): array
    {
        try {
            $fallbackKey = $this->generateUsersCacheKey([]);
            $cachedItem = $this->cache->get($fallbackKey, static fn () => ['data' => []]);

            if (! empty($cachedItem['data'])) {
                $this->logger->info('Fallback users retrieved from cache', [
                    'count' => count($cachedItem['data']),
                ]);

                return $this->applyClientSideFiltering($cachedItem, $params);
            }

            return ['data' => []];
        } catch (\Exception $e) {
            $this->logger->error('Fallback failed', ['error' => $e->getMessage()]);
            return ['data' => []];
        }
    }

    private function getFallbackUser(int $id): array
    {
        try {
            $cacheKey = self::USER_CACHE_KEY_PREFIX . $id;
            $cachedItem = $this->cache->get($cacheKey, static fn () => []);

            if (! empty($cachedItem)) {
                $this->logger->info('Fallback user retrieved from cache', ['id' => $id]);
                return $cachedItem;
            }

            throw PhoenixApiException::notFound('User', $id);
        } catch (\Exception $e) {
            $this->logger->error('Single user fallback failed', [
                'id' => $id,
                'error' => $e->getMessage(),
            ]);
            throw PhoenixApiException::notFound('User', $id);
        }
    }

    private function applyClientSideFiltering(array $apiResponse, array $params): array
    {
        if (empty($params) || empty($apiResponse['data'])) {
            return $apiResponse;
        }

        $filteredData = $apiResponse['data'];

        foreach ($params as $key => $value) {
            if ($value === null || $value === '') {
                continue;
            }

            $filteredData = array_filter($filteredData, static function ($user) use ($key, $value) {
                return match ($key) {
                    'first_name' => isset($user['first_name']) &&
                        str_contains(strtolower($user['first_name']), strtolower($value)),
                    'last_name' => isset($user['last_name']) &&
                        str_contains(strtolower($user['last_name']), strtolower($value)),
                    'gender' => isset($user['gender']) && $user['gender'] === $value,
                    default => true
                };
            });
        }

        return [
            'data' => array_values($filteredData),
            'meta' => $apiResponse['meta'] ?? [],
        ];
    }

    private function invalidateUsersCache(): void
    {
        try {
            $this->cache->delete(self::USERS_CACHE_KEY);

            $this->logger->info('Users cache invalidated');
        } catch (\Exception $e) {
            $this->logger->error('Cache invalidation failed', ['error' => $e->getMessage()]);
        }
    }
}
