<?php

declare(strict_types=1);

namespace Modules\Core\Services\WordPress;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
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
        $endpoint = $this->qualifyResource($resource);

        return $this->request('GET', $endpoint, ['query' => $query]);
    }

    /**
     * @param array<string, mixed> $options
     *
     * @return array<int|string, mixed>
     */
    private function request(string $method, string $uri, array $options = []): array
    {
        try {
            $response = $this->client->request($method, $uri, $options);
        } catch (GuzzleException $exception) {
            throw WordPressRequestException::fromThrowable($method, $uri, $exception);
        }

        return $this->decodeResponse($method, $uri, $response);
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
}

