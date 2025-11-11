<?php

declare(strict_types=1);

namespace Modules\Core\Http\Controllers\WordPress;

use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;
use Modules\Core\Http\Requests\StoreWpTokenRequest;
use Modules\Core\Models\WpToken;
use Modules\Core\Services\WordPress\Contracts\SdkContract;
use Modules\Core\Services\WordPress\Exceptions\WordPressRequestException;

final class TokenController extends Controller
{
    public function __construct(private readonly SdkContract $sdk)
    {
    }

    public function store(StoreWpTokenRequest $request): JsonResponse
    {
        $credentials = $request->validated();
        $remember = (bool) ($credentials['remember'] ?? false);

        try {
            $response = $this->sdk->token(
                $credentials['username'],
                $credentials['password']
            );
        } catch (WordPressRequestException $exception) {
            $sourceStatus = $exception->sourceStatus();

            return ApiResponse::error(
                code: 'wordpress.token_failed',
                message: $exception->getMessage(),
                meta: array_filter([
                    'source_status' => $sourceStatus,
                    'remembered' => false,
                ], static fn ($value) => $value !== null),
                data: null,
                status: $sourceStatus ?? 502
            );
        }

        $tokenModel = null;
        if ($remember) {
            $tokenModel = WpToken::updateOrCreate(
                ['username' => $credentials['username']],
                [
                    'token' => $response['token'] ?? '',
                    'payload' => $response,
                ]
            );
        }

        $message = $remember ? 'Token stored successfully.' : 'Token retrieved successfully.';
        $maskedToken = $remember ? $this->maskToken($tokenModel?->token) : null;

        return ApiResponse::success(
            code: 'wordpress.token_created',
            message: $message,
            data: [
                'id' => $tokenModel?->id,
                'remembered' => $remember,
                'masked_token' => $maskedToken,
                'username' => $remember ? $credentials['username'] : null,
            ],
            status: 201
        );
    }

    public function show(): JsonResponse
    {
        $token = WpToken::query()->latest('updated_at')->first();

        if ($token === null) {
            return ApiResponse::success(
                code: 'wordpress.token_absent',
                message: 'No remembered token present.',
                data: [
                    'remembered' => false,
                    'masked_token' => null,
                ]
            );
        }

        return ApiResponse::success(
            code: 'wordpress.token_remembered',
            message: 'WordPress token is remembered.',
            data: [
                'remembered' => true,
                'masked_token' => $this->maskToken($token->token),
                'username' => $token->username,
            ]
        );
    }

    public function destroy(): JsonResponse
    {
        $token = WpToken::query()->latest('updated_at')->first();

        if ($token === null) {
            return ApiResponse::success(
                code: 'wordpress.token_absent',
                message: 'No remembered token present.',
                data: [
                    'remembered' => false,
                    'masked_token' => null,
                ]
            );
        }

        $token->delete();

        return ApiResponse::success(
            code: 'wordpress.token_cleared',
            message: 'Remembered token removed.',
            data: [
                'remembered' => false,
                'masked_token' => null,
                'username' => null,
            ]
        );
    }

    private function maskToken(?string $token): ?string
    {
        if (! is_string($token) || $token === '') {
            return null;
        }

        $length = Str::length($token);

        if ($length <= 8) {
            return Str::mask($token, '*', 1);
        }

        $visiblePrefix = 4;
        $visibleSuffix = 3;
        $maskLength = max($length - ($visiblePrefix + $visibleSuffix), 0);

        return Str::substr($token, 0, $visiblePrefix)
            .str_repeat('*', $maskLength)
            .Str::substr($token, -$visibleSuffix);
    }

}
