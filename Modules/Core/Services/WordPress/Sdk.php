<?php

declare(strict_types=1);

namespace Modules\Core\Services\WordPress;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use JsonException;
use Modules\Core\Services\WordPress\Contracts\SdkContract;
use Modules\Core\Services\WordPress\Exceptions\WordPressRequestException;
use Psr\Http\Message\ResponseInterface;

final class Sdk implements SdkContract
{
    private readonly string $namespace;

    public function __construct(
        private readonly ClientInterface $client,
        string $namespace = 'wp/v2'
    ) {
        $this->namespace = trim($namespace, '/');
    }

    public function posts(array $query = []): array
    {
        return $this->get('posts', $query);
    }

    public function post(int $id, array $query = []): array
    {
        return $this->get(sprintf('posts/%d', $id), $query);
    }

    public function pages(array $query = []): array
    {
        return $this->get('pages', $query);
    }

    public function media(array $query = []): array
    {
        return $this->get('media', $query);
    }

    public function categories(array $query = []): array
    {
        return $this->get('categories', $query);
    }

    public function tags(array $query = []): array
    {
        return $this->get('tags', $query);
    }

    public function users(array $query = []): array
    {
        return $this->get('users', $query);
    }

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
     * @param array<string, mixed> $query
     *
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
     * @param array<string, mixed> $options
     *
     * @return array<int|string, mixed>
     */
    private function request(string $method, string $uri, array $options = [], bool $withinNamespace = true): array
    {
        $endpoint = $withinNamespace ? $this->qualifyResource($uri) : ltrim($uri, '/');

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
            throw WordPressRequestException::invalidJson($method, $uri, $exception);
        }

        return $decoded;
    }

    private function qualifyResource(string $resource): string
    {
        $resource = ltrim($resource, '/');

        return sprintf('%s/%s', $this->namespace, $resource);
    }

    /**
     * @param array<string, mixed> $options
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
     * @param array<string, mixed> $options
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
     * @param array<string, mixed> $options
     * @param array<int|string, mixed> $response
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
     * @param array<string, mixed> $options
     *
     * @return array<string, mixed>
     */
    private function sanitizeOptions(array $options): array
    {
        if (isset($options['json']['password']) && is_string($options['json']['password'])) {
            $options['json']['password'] = Str::mask($options['json']['password'], '*', 2);
        }

        if (isset($options['form_params']['password']) && is_string($options['form_params']['password'])) {
            $options['form_params']['password'] = Str::mask($options['form_params']['password'], '*', 2);
        }

        return $options;
    }

    /**
     * @param array<int|string, mixed> $response
     *
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
            .str_repeat('*', $maskLength)
            .Str::substr($token, -$visibleSuffix);
    }
}

