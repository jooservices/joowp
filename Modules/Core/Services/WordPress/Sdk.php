<?php

declare(strict_types=1);

namespace Modules\Core\Services\WordPress;

use Closure;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use JsonException;
use Modules\Core\Services\Cache\CacheHelper;
use Modules\Core\Services\WordPress\Contracts\SdkContract;
use Modules\Core\Services\WordPress\Exceptions\WordPressRequestException;
use Psr\Http\Message\ResponseInterface;

final class Sdk implements SdkContract
{
    private readonly string $namespace;

    private readonly ?Closure $tokenResolver;

    public function __construct(
        private readonly ClientInterface $client,
        private readonly CacheRepository $cache,
        private readonly CacheHelper $cacheHelper,
        ?Closure $tokenResolver = null,
        string $namespace = 'wp/v2'
    ) {
        $this->tokenResolver = $tokenResolver;
        $this->namespace = trim($namespace, '/');
    }

    public function posts(array $query = []): array
    {
        // Use version-based cache keys for list invalidation (similar to categories)
        $versionValue = $this->cache->get('wp.posts.version', 0);
        // @phpstan-ignore-next-line - Cache returns mixed, but we ensure int with max()
        $version = max(0, (int) $versionValue);
        $cacheKey = sprintf('wp.posts.v%d.%s', $version, md5(json_encode($query, JSON_THROW_ON_ERROR)));

        return $this->cache->remember($cacheKey, now()->addMinutes(5), function () use ($query): array {
            return $this->get('posts', $query);
        });
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array<int|string, mixed>
     */
    public function post(int $id, array $query = []): array
    {
        $cacheKey = sprintf('wp.post.%d.%s', $id, md5(json_encode($query, JSON_THROW_ON_ERROR)));

        return $this->cache->remember($cacheKey, now()->addMinutes(10), function () use ($id, $query): array {
            return $this->get(sprintf('posts/%d', $id), $query);
        });
    }

    public function pages(array $query = []): array
    {
        return $this->get('pages', $query);
    }

    public function media(array $query = []): array
    {
        $cacheKey = 'wp.media.' . md5(json_encode($query, JSON_THROW_ON_ERROR));

        return $this->cache->remember($cacheKey, now()->addMinutes(15), function () use ($query): array {
            return $this->get('media', $query);
        });
    }

    public function categories(array $query = []): array
    {
        $versionValue = $this->cache->get('wp.categories.version', 0);
        // @phpstan-ignore-next-line - Cache returns mixed, but we ensure int with max()
        $version = max(0, (int) $versionValue);
        $cacheKey = sprintf('wp.categories.v%d.%s', $version, md5(json_encode($query, JSON_THROW_ON_ERROR)));

        return $this->cache->remember($cacheKey, now()->addMinutes(30), function () use ($query): array {
            return $this->get('categories', $query);
        });
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array<int|string, mixed>
     */
    public function category(int $id, array $query = []): array
    {
        $cacheKey = sprintf('wp.category.%d.%s', $id, md5(json_encode($query, JSON_THROW_ON_ERROR)));

        return $this->cache->remember($cacheKey, now()->addMinutes(30), function () use ($id, $query): array {
            return $this->get(sprintf('categories/%d', $id), $query);
        });
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<int|string, mixed>
     */
    public function createCategory(array $payload): array
    {
        $result = $this->request(
            method: 'POST',
            uri: 'categories',
            options: ['json' => $payload]
        );

        $this->invalidateCategoryListCache();

        return $result;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<int|string, mixed>
     */
    public function updateCategory(int $id, array $payload): array
    {
        $result = $this->request(
            method: 'POST',
            uri: sprintf('categories/%d', $id),
            options: ['json' => $payload]
        );

        $this->invalidateCategoryCache($id);
        $this->invalidateCategoryListCache();

        return $result;
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array<int|string, mixed>
     */
    public function deleteCategory(int $id, array $query = []): array
    {
        $result = $this->request(
            method: 'DELETE',
            uri: sprintf('categories/%d', $id),
            options: ['query' => $query]
        );

        $this->invalidateCategoryCache($id);
        $this->invalidateCategoryListCache();

        return $result;
    }

    public function tags(array $query = []): array
    {
        $cacheKey = 'wp.tags.' . md5(json_encode($query, JSON_THROW_ON_ERROR));

        return $this->cache->remember($cacheKey, now()->addMinutes(30), function () use ($query): array {
            return $this->get('tags', $query);
        });
    }

    public function users(array $query = []): array
    {
        $cacheKey = 'wp.users.' . md5(json_encode($query, JSON_THROW_ON_ERROR));

        return $this->cache->remember($cacheKey, now()->addMinutes(60), function () use ($query): array {
            return $this->get('users', $query);
        });
    }

    /**
     * @return array<int|string, mixed>
     */
    public function token(string $username, string $password): array
    {
        return $this->request(
            method: 'POST',
            uri: 'jwt-auth/v1/token',
            options: [
                'json' => [
                    'username' => $username,
                    'password' => $password,
                ],
            ],
            withinNamespace: false
        );
    }

    public function search(array $query = []): array
    {
        return $this->getFromNamespace('search', $query);
    }

    public function get(string $resource, array $query = []): array
    {
        return $this->getFromNamespace($resource, $query);
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array<int|string, mixed>
     */
    private function getFromNamespace(string $resource, array $query = []): array
    {
        return $this->request(
            method: 'GET',
            uri: $resource,
            options: ['query' => $query]
        );
    }

    /**
     * @param  array<string, mixed>  $options
     * @return array<int|string, mixed>
     */
    private function request(string $method, string $uri, array $options = [], bool $withinNamespace = true): array
    {
        $endpoint = $withinNamespace ? $this->qualifyResource($uri) : ltrim($uri, '/');

        $options = $this->mergeAuthorization($options);

        $this->logExternalDispatch($method, $endpoint, $options);

        try {
            $response = $this->client->request($method, $endpoint, $options);
        } catch (GuzzleException $exception) {
            $this->logExternalFailure($method, $endpoint, $options, $exception->getMessage());

            throw WordPressRequestException::fromThrowable($method, $endpoint, $exception);
        }

        $decoded = $this->decodeResponse($method, $endpoint, $response);

        $this->logExternalResponse($method, $endpoint, $options, $decoded);

        return $decoded;
    }

    /**
     * @return array<int|string, mixed>
     */
    private function decodeResponse(string $method, string $uri, ResponseInterface $response): array
    {
        try {
            /** @var array<int|string, mixed> $decoded */
            $decoded = json_decode(
                $response->getBody()->getContents(),
                true,
                512,
                JSON_THROW_ON_ERROR
            );
        } catch (JsonException $exception) {
            throw WordPressRequestException::invalidJson($method, $uri, $exception, $response->getStatusCode());
        }

        return $decoded;
    }

    private function qualifyResource(string $resource): string
    {
        $resource = ltrim($resource, '/');

        return sprintf('%s/%s', $this->namespace, $resource);
    }

    /**
     * @param  array<string, mixed>  $options
     */
    private function logExternalDispatch(string $method, string $uri, array $options): void
    {
        Log::channel('external')->info('Dispatching WordPress API request', [
            'method' => strtoupper($method),
            'uri' => $uri,
            'options' => $this->sanitizeOptions($options),
        ]);
    }

    /**
     * @param  array<string, mixed>  $options
     */
    private function logExternalFailure(string $method, string $uri, array $options, string $message): void
    {
        Log::channel('external')->info('WordPress API request failed', [
            'method' => strtoupper($method),
            'uri' => $uri,
            'options' => $this->sanitizeOptions($options),
            'error' => $message,
        ]);
    }

    /**
     * @param  array<string, mixed>  $options
     * @param  array<int|string, mixed>  $response
     */
    private function logExternalResponse(string $method, string $uri, array $options, array $response): void
    {
        Log::channel('external')->info('Received WordPress API response', [
            'method' => strtoupper($method),
            'uri' => $uri,
            'options' => $this->sanitizeOptions($options),
            'response' => $this->sanitizeResponse($response),
        ]);
    }

    /**
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    private function sanitizeOptions(array $options): array
    {
        $headers = $options['headers'] ?? null;
        if (is_array($headers) && isset($headers['Authorization']) && is_string($headers['Authorization'])) {
            $headers['Authorization'] = $this->maskToken($headers['Authorization']);
            $options['headers'] = $headers;
        }

        $json = $options['json'] ?? null;
        if (is_array($json) && isset($json['password']) && is_string($json['password'])) {
            $json['password'] = Str::mask($json['password'], '*', 2);
            $options['json'] = $json;
        }

        $formParams = $options['form_params'] ?? null;
        if (is_array($formParams) && isset($formParams['password']) && is_string($formParams['password'])) {
            $formParams['password'] = Str::mask($formParams['password'], '*', 2);
            $options['form_params'] = $formParams;
        }

        return $options;
    }

    /**
     * @param  array<int|string, mixed>  $response
     * @return array<int|string, mixed>
     */
    private function sanitizeResponse(array $response): array
    {
        if (isset($response['token']) && is_string($response['token'])) {
            $response['token'] = $this->maskToken($response['token']);
        }

        return $response;
    }

    private function maskToken(string $token): string
    {
        if (Str::length($token) <= 8) {
            return Str::mask($token, '*', 1);
        }

        $visiblePrefix = 4;
        $visibleSuffix = 3;
        $maskLength = max(Str::length($token) - ($visiblePrefix + $visibleSuffix), 0);

        return Str::substr($token, 0, $visiblePrefix)
            . str_repeat('*', $maskLength)
            . Str::substr($token, -$visibleSuffix);
    }

    /**
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    private function mergeAuthorization(array $options): array
    {
        $headers = $options['headers'] ?? [];
        if (! is_array($headers)) {
            $headers = [];
        }

        $authorization = $this->authorizationHeaders();

        if ($authorization !== []) {
            $headers = array_merge($headers, $authorization);
            $options['headers'] = $headers;
        }

        return $options;
    }

    /**
     * @return array<string, string>
     */
    private function authorizationHeaders(): array
    {
        if ($this->tokenResolver === null) {
            return [];
        }

        $token = ($this->tokenResolver)();

        if (! is_string($token) || $token === '') {
            return [];
        }

        return [
            'Authorization' => 'Bearer ' . $token,
        ];
    }

    /**
     * Invalidate cache for a specific category
     * Clears all cached variations: wp.category.{id}.{queryHash}
     */
    public function invalidateCategoryCache(int $id): void
    {
        $prefix = sprintf('wp.category.%d.', $id);
        $this->clearCacheByPrefix($prefix);
    }

    /**
     * Invalidate all category list caches
     * Note: Database cache doesn't support wildcards, so this is a best-effort approach
     * For production with Redis, consider using cache tags
     */
    private function invalidateCategoryListCache(): void
    {
        // Since database cache doesn't support wildcards, we can't easily invalidate
        // all "wp.categories.*" keys. Options:
        // 1. Track keys in a separate cache entry
        // 2. Use versioned cache keys (e.g., wp.categories.v1.*)
        // 3. Use Redis with tags in production
        // For now, we'll implement a simple version bump approach
        $versionKey = 'wp.categories.version';
        $versionValue = $this->cache->get($versionKey, 0);
        // @phpstan-ignore-next-line - Cache returns mixed, but we ensure int with max()
        $version = max(0, (int) $versionValue);
        $this->cache->put($versionKey, $version + 1, now()->addDays(1));
    }

    /**
     * Invalidate cache for a specific post
     * Clears all cached variations: wp.post.{id}.{queryHash}
     *
     * Note: This method should be called after post mutations (create/update/delete)
     * to ensure cache consistency.
     */
    public function invalidatePostCache(int $id): void
    {
        $prefix = sprintf('wp.post.%d.', $id);
        $this->clearCacheByPrefix($prefix);
    }

    /**
     * Invalidate posts list cache
     * Clears all cached variations: wp.posts.{queryHash}
     *
     * Note: This method should be called after post mutations (create/update/delete)
     * to ensure posts list cache is invalidated. Uses version-based invalidation
     * similar to categories for database cache compatibility.
     */
    public function invalidatePostsListCache(): void
    {
        // Similar to categories, we use version-based invalidation for database cache
        // Since database cache doesn't support wildcards, version bump invalidates all list entries
        $versionKey = 'wp.posts.version';
        $versionValue = $this->cache->get($versionKey, 0);
        // @phpstan-ignore-next-line - Cache returns mixed, but we ensure int with max()
        $version = max(0, (int) $versionValue);
        $this->cache->put($versionKey, $version + 1, now()->addDays(1));
    }

    /**
     * Clear cache entries by prefix
     * Delegates to CacheHelper to handle infrastructure concerns
     */
    private function clearCacheByPrefix(string $prefix): void
    {
        $this->cacheHelper->clearByPrefix($prefix);
    }
}
