<?php

declare(strict_types=1);

namespace Tests\Unit;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Mockery;
use Mockery\MockInterface;
use Modules\Core\Services\Cache\CacheHelper;
use Modules\Core\Services\WordPress\Contracts\SdkContract;
use Modules\Core\Services\WordPress\Exceptions\WordPressRequestException;
use Modules\Core\Services\WordPress\Sdk;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ResponseInterface;
use Tests\TestCase;

#[CoversClass(Sdk::class)]
class WordPressSdkTest extends TestCase
{
    #[Test]
    public function it_fetches_posts(): void
    {
        $response = new Response(
            200,
            ['Content-Type' => 'application/json'],
            json_encode([['id' => 1, 'title' => ['rendered' => 'Hello']]], JSON_THROW_ON_ERROR)
        );

        $client = $this->mockClient('GET', 'wp/v2/posts', ['query' => ['per_page' => 5]], $response);

        $this->expectExternalLogs(2);

        $sdk = $this->makeSdk($client);

        $posts = $sdk->posts(['per_page' => 5]);

        $this->assertSame(1, $posts[0]['id']);
        $this->assertSame('Hello', $posts[0]['title']['rendered']);
    }

    #[Test]
    public function it_wraps_transport_exceptions(): void
    {
        $exception = new ConnectException(
            'DNS error',
            new Request('GET', 'wp/v2/posts')
        );

        $client = Mockery::mock(ClientInterface::class);
        $client
            ->shouldReceive('request')
            ->once()
            ->with('GET', 'wp/v2/posts', ['query' => []])
            ->andThrow($exception);

        $this->expectExternalLogs(2);

        $sdk = $this->makeSdk($client);

        $this->expectException(WordPressRequestException::class);

        $sdk->posts();
    }

    #[Test]
    public function it_exchanges_credentials_for_a_token(): void
    {
        $response = new Response(
            200,
            ['Content-Type' => 'application/json'],
            json_encode([
                'token' => 'abcdef123456',
                'user_email' => 'demo@example.com',
            ], JSON_THROW_ON_ERROR)
        );

        $client = $this->mockClient(
            'POST',
            'jwt-auth/v1/token',
            [
                'json' => [
                    'username' => 'demo',
                    'password' => 'secret',
                ],
            ],
            $response
        );

        Log::shouldReceive('channel')
            ->twice()
            ->with('external')
            ->andReturnSelf();

        Log::shouldReceive('info')
            ->once()
            ->withArgs(function (string $message, array $context): bool {
                $this->assertSame('Dispatching WordPress API request', $message);
                $this->assertSame('POST', $context['method']);
                $this->assertSame('jwt-auth/v1/token', $context['uri']);
                $this->assertSame('se****', $context['options']['json']['password']);

                return true;
            });

        Log::shouldReceive('info')
            ->once()
            ->withArgs(function (string $message, array $context): bool {
                $this->assertSame('Received WordPress API response', $message);
                $this->assertSame('POST', $context['method']);
                $this->assertSame('jwt-auth/v1/token', $context['uri']);
                $this->assertSame('abcd*****456', $context['response']['token']);

                return true;
            });

        $sdk = $this->makeSdk($client);

        $result = $sdk->token('demo', 'secret');

        $this->assertSame('abcdef123456', $result['token']);
    }

    #[Test]
    public function it_fetches_categories(): void
    {
        $response = new Response(
            200,
            ['Content-Type' => 'application/json'],
            json_encode([['id' => 1, 'name' => 'News']], JSON_THROW_ON_ERROR)
        );

        $client = $this->mockClient('GET', 'wp/v2/categories', ['query' => ['per_page' => 10]], $response);

        $this->expectExternalLogs(2);

        $sdk = $this->makeSdk($client);

        $categories = $sdk->categories(['per_page' => 10]);

        $this->assertSame(1, $categories[0]['id']);
        $this->assertSame('News', $categories[0]['name']);
    }

    #[Test]
    public function it_fetches_single_category(): void
    {
        $response = new Response(
            200,
            ['Content-Type' => 'application/json'],
            json_encode(['id' => 1, 'name' => 'News', 'slug' => 'news'], JSON_THROW_ON_ERROR)
        );

        $client = $this->mockClient('GET', 'wp/v2/categories/1', ['query' => []], $response);

        $this->expectExternalLogs(2);

        $sdk = $this->makeSdk($client);

        $category = $sdk->category(1);

        $this->assertSame(1, $category['id']);
        $this->assertSame('News', $category['name']);
        $this->assertSame('news', $category['slug']);
    }

    #[Test]
    public function it_creates_category(): void
    {
        $response = new Response(
            201,
            ['Content-Type' => 'application/json'],
            json_encode(['id' => 10, 'name' => 'Technology'], JSON_THROW_ON_ERROR)
        );

        $payload = ['name' => 'Technology', 'slug' => 'technology'];

        $client = $this->mockClient('POST', 'wp/v2/categories', ['json' => $payload], $response);

        $this->expectExternalLogs(2);

        $sdk = $this->makeSdk($client);

        $category = $sdk->createCategory($payload);

        $this->assertSame(10, $category['id']);
        $this->assertSame('Technology', $category['name']);
    }

    #[Test]
    public function it_updates_category(): void
    {
        $response = new Response(
            200,
            ['Content-Type' => 'application/json'],
            json_encode(['id' => 10, 'name' => 'Tech', 'slug' => 'tech'], JSON_THROW_ON_ERROR)
        );

        $payload = ['name' => 'Tech'];

        $client = $this->mockClient('POST', 'wp/v2/categories/10', ['json' => $payload], $response);

        $this->expectExternalLogs(2);

        $sdk = $this->makeSdk($client);

        $category = $sdk->updateCategory(10, $payload);

        $this->assertSame(10, $category['id']);
        $this->assertSame('Tech', $category['name']);
    }

    #[Test]
    public function it_deletes_category(): void
    {
        $response = new Response(
            200,
            ['Content-Type' => 'application/json'],
            json_encode(['deleted' => true, 'previous' => ['id' => 10]], JSON_THROW_ON_ERROR)
        );

        $client = $this->mockClient('DELETE', 'wp/v2/categories/10', ['query' => ['force' => true]], $response);

        $this->expectExternalLogs(2);

        $sdk = $this->makeSdk($client);

        $result = $sdk->deleteCategory(10, ['force' => true]);

        $this->assertTrue($result['deleted']);
    }

    #[Test]
    public function it_fetches_tags(): void
    {
        $response = new Response(
            200,
            ['Content-Type' => 'application/json'],
            json_encode([['id' => 1, 'name' => 'PHP']], JSON_THROW_ON_ERROR)
        );

        $client = $this->mockClient('GET', 'wp/v2/tags', ['query' => []], $response);

        $this->expectExternalLogs(2);

        $sdk = $this->makeSdk($client);

        $tags = $sdk->tags();

        $this->assertSame(1, $tags[0]['id']);
        $this->assertSame('PHP', $tags[0]['name']);
    }

    #[Test]
    public function it_fetches_media(): void
    {
        $response = new Response(
            200,
            ['Content-Type' => 'application/json'],
            json_encode([['id' => 1, 'title' => ['rendered' => 'Image']]], JSON_THROW_ON_ERROR)
        );

        $client = $this->mockClient('GET', 'wp/v2/media', ['query' => []], $response);

        $this->expectExternalLogs(2);

        $sdk = $this->makeSdk($client);

        $media = $sdk->media();

        $this->assertSame(1, $media[0]['id']);
    }

    #[Test]
    public function it_fetches_users(): void
    {
        $response = new Response(
            200,
            ['Content-Type' => 'application/json'],
            json_encode([['id' => 1, 'name' => 'Admin']], JSON_THROW_ON_ERROR)
        );

        $client = $this->mockClient('GET', 'wp/v2/users', ['query' => []], $response);

        $this->expectExternalLogs(2);

        $sdk = $this->makeSdk($client);

        $users = $sdk->users();

        $this->assertSame(1, $users[0]['id']);
        $this->assertSame('Admin', $users[0]['name']);
    }

    #[Test]
    public function it_handles_invalid_json_response(): void
    {
        $response = new Response(
            200,
            ['Content-Type' => 'application/json'],
            'invalid json'
        );

        $client = Mockery::mock(ClientInterface::class);
        $client
            ->shouldReceive('request')
            ->once()
            ->with('GET', 'wp/v2/posts', ['query' => []])
            ->andReturn($response);

        // Log dispatch only (invalid JSON exception is thrown but not logged as failure)
        Log::shouldReceive('channel')
            ->once()
            ->with('external')
            ->andReturnSelf();

        Log::shouldReceive('info')
            ->once()
            ->with('Dispatching WordPress API request', Mockery::any());

        $sdk = $this->makeSdk($client);

        $this->expectException(WordPressRequestException::class);

        $sdk->posts();
    }

    #[Test]
    public function it_caches_posts_response(): void
    {
        $response = new Response(
            200,
            ['Content-Type' => 'application/json'],
            json_encode([['id' => 1, 'title' => ['rendered' => 'Cached']]], JSON_THROW_ON_ERROR)
        );

        $client = Mockery::mock(ClientInterface::class);
        $client
            ->shouldReceive('request')
            ->once() // Should only be called once due to caching
            ->with('GET', 'wp/v2/posts', ['query' => []])
            ->andReturn($response);

        $this->expectExternalLogs(2);

        $cache = Cache::store('array');
        $sdk = $this->makeSdk($client, $cache);

        // First call - cache miss, should call API
        $posts1 = $sdk->posts();
        $this->assertSame(1, $posts1[0]['id']);

        // Second call - cache hit, should NOT call API again
        $posts2 = $sdk->posts();
        $this->assertSame(1, $posts2[0]['id']);
        $this->assertSame('Cached', $posts2[0]['title']['rendered']);
    }

    #[Test]
    public function it_caches_categories_with_versioning(): void
    {
        $response = new Response(
            200,
            ['Content-Type' => 'application/json'],
            json_encode([['id' => 1, 'name' => 'News']], JSON_THROW_ON_ERROR)
        );

        $client = Mockery::mock(ClientInterface::class);
        $client
            ->shouldReceive('request')
            ->once()
            ->with('GET', 'wp/v2/categories', ['query' => []])
            ->andReturn($response);

        $this->expectExternalLogs(2);

        $cache = Cache::store('array');
        $sdk = $this->makeSdk($client, $cache);

        $categories = $sdk->categories();
        $this->assertSame(1, $categories[0]['id']);

        // Verify cache key includes version (default version is 0)
        $version = (int) $cache->get('wp.categories.version', 0);
        $expectedKey = sprintf('wp.categories.v%d.%s', $version, md5(json_encode([], JSON_THROW_ON_ERROR)));
        $this->assertNotNull($cache->get($expectedKey));
    }

    #[Test]
    public function it_invalidates_category_cache_on_update(): void
    {
        $getResponse = new Response(
            200,
            ['Content-Type' => 'application/json'],
            json_encode(['id' => 10, 'name' => 'Tech'], JSON_THROW_ON_ERROR)
        );

        $updateResponse = new Response(
            200,
            ['Content-Type' => 'application/json'],
            json_encode(['id' => 10, 'name' => 'Updated Tech'], JSON_THROW_ON_ERROR)
        );

        $client = Mockery::mock(ClientInterface::class);
        $client
            ->shouldReceive('request')
            ->once()
            ->with('GET', 'wp/v2/categories/10', ['query' => []])
            ->andReturn($getResponse);

        $client
            ->shouldReceive('request')
            ->once()
            ->with('POST', 'wp/v2/categories/10', ['json' => ['name' => 'Updated Tech']])
            ->andReturn($updateResponse);

        $this->expectExternalLogs(4);

        $cache = Cache::store('array');
        $sdk = $this->makeSdk($client, $cache);

        // Fetch category (cached)
        $category = $sdk->category(10);
        $this->assertSame('Tech', $category['name']);

        // Verify category was cached
        $cacheKey = sprintf('wp.category.10.%s', md5(json_encode([], JSON_THROW_ON_ERROR)));
        $this->assertNotNull($cache->get($cacheKey));

        // Get initial version
        $initialVersion = (int) $cache->get('wp.categories.version', 0);

        // Update category (should invalidate cache and bump version)
        $updated = $sdk->updateCategory(10, ['name' => 'Updated Tech']);
        $this->assertSame('Updated Tech', $updated['name']);

        // Verify version was bumped (invalidates all category list caches)
        $newVersion = (int) $cache->get('wp.categories.version', 0);
        $this->assertGreaterThan($initialVersion, $newVersion);
    }

    private function mockClient(string $method, string $uri, array $options, ResponseInterface $response): ClientInterface&MockInterface
    {
        $client = Mockery::mock(ClientInterface::class);

        $client
            ->shouldReceive('request')
            ->once()
            ->with($method, $uri, $options)
            ->andReturn($response);

        return $client;
    }

    private function makeSdk(ClientInterface $client, ?CacheRepository $cache = null): SdkContract
    {
        $cache = $cache ?? Cache::store('array');
        $cacheHelper = new CacheHelper($cache);

        return new Sdk($client, $cache, $cacheHelper);
    }

    private function expectExternalLogs(int $times): void
    {
        Log::shouldReceive('channel')
            ->times($times)
            ->with('external')
            ->andReturnSelf();

        Log::shouldReceive('info')
            ->times($times);
    }
}
