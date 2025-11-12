<?php

declare(strict_types=1);

namespace Tests\Unit;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Log;
use Mockery;
use Mockery\MockInterface;
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

    private function makeSdk(ClientInterface $client): SdkContract
    {
        return new Sdk($client);
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
