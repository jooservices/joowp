<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Responses;

use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Tests\TestCase;

final class ApiResponseTest extends TestCase
{
    public function test_success_returns_correct_envelope(): void
    {
        $response = ApiResponse::success(
            code: 'test.success',
            message: 'Operation successful',
            data: ['id' => 1, 'name' => 'Test'],
            meta: ['timestamp' => '2025-01-15'],
            status: 200
        );

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(200, $response->getStatusCode());

        $json = json_decode($response->getContent(), true);
        $this->assertTrue($json['ok']);
        $this->assertSame('test.success', $json['code']);
        $this->assertSame(200, $json['status']);
        $this->assertSame('Operation successful', $json['message']);
        $this->assertSame(['id' => 1, 'name' => 'Test'], $json['data']);
        $this->assertIsArray($json['meta']);
        $this->assertSame('2025-01-15', $json['meta']['timestamp']);
    }

    public function test_success_with_minimal_parameters(): void
    {
        $response = ApiResponse::success(
            code: 'test.success',
            message: 'Success'
        );

        $this->assertSame(200, $response->getStatusCode());

        $json = json_decode($response->getContent(), true);
        $this->assertTrue($json['ok']);
        $this->assertSame('test.success', $json['code']);
        $this->assertSame(200, $json['status']);
        $this->assertSame('Success', $json['message']);
        $this->assertSame([], $json['data']);
        $this->assertIsArray($json['meta']);
    }

    public function test_success_with_custom_status_code(): void
    {
        $response = ApiResponse::success(
            code: 'test.created',
            message: 'Created',
            data: ['id' => 1],
            status: 201
        );

        $this->assertSame(201, $response->getStatusCode());

        $json = json_decode($response->getContent(), true);
        $this->assertSame(201, $json['status']);
    }

    public function test_error_returns_correct_envelope(): void
    {
        $response = ApiResponse::error(
            code: 'test.error',
            message: 'Operation failed',
            meta: ['error_code' => 'E001'],
            data: null,
            status: 400
        );

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(400, $response->getStatusCode());

        $json = json_decode($response->getContent(), true);
        $this->assertFalse($json['ok']);
        $this->assertSame('test.error', $json['code']);
        $this->assertSame(400, $json['status']);
        $this->assertSame('Operation failed', $json['message']);
        $this->assertNull($json['data']);
        $this->assertIsArray($json['meta']);
        $this->assertSame('E001', $json['meta']['error_code']);
    }

    public function test_error_with_minimal_parameters(): void
    {
        $response = ApiResponse::error(
            code: 'test.error',
            message: 'Error occurred'
        );

        $this->assertSame(400, $response->getStatusCode());

        $json = json_decode($response->getContent(), true);
        $this->assertFalse($json['ok']);
        $this->assertSame('test.error', $json['code']);
        $this->assertSame(400, $json['status']);
        $this->assertSame('Error occurred', $json['message']);
        $this->assertNull($json['data']);
        $this->assertIsArray($json['meta']);
    }

    public function test_error_with_data(): void
    {
        $response = ApiResponse::error(
            code: 'test.validation_error',
            message: 'Validation failed',
            meta: [],
            data: ['errors' => ['name' => ['Required']]],
            status: 422
        );

        $json = json_decode($response->getContent(), true);
        $this->assertFalse($json['ok']);
        $this->assertSame(422, $json['status']);
        $this->assertSame(['errors' => ['name' => ['Required']]], $json['data']);
    }

    public function test_error_with_custom_status_code(): void
    {
        $response = ApiResponse::error(
            code: 'test.not_found',
            message: 'Resource not found',
            status: 404
        );

        $this->assertSame(404, $response->getStatusCode());

        $json = json_decode($response->getContent(), true);
        $this->assertSame(404, $json['status']);
    }

    public function test_meta_is_serialized_as_object_in_json(): void
    {
        $successResponse = ApiResponse::success(
            code: 'test.success',
            message: 'Success',
            meta: ['key' => 'value']
        );

        $errorResponse = ApiResponse::error(
            code: 'test.error',
            message: 'Error',
            meta: ['key' => 'value']
        );

        $this->assertIsArray(json_decode($successResponse->getContent(), true)['meta']);
        $this->assertIsArray(json_decode($errorResponse->getContent(), true)['meta']);
    }

    public function test_empty_meta_is_serialized_as_object_in_json(): void
    {
        $response = ApiResponse::success(
            code: 'test.success',
            message: 'Success',
            meta: []
        );

        $this->assertIsArray(json_decode($response->getContent(), true)['meta']);
    }
}
