<?php

declare(strict_types=1);

namespace Modules\Core\Http\Controllers\WordPress;

use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Core\Http\Requests\StoreWpTokenRequest;
use Modules\Core\Models\WpToken;
use Modules\Core\Services\WordPress\Contracts\SdkContract;
use Modules\Core\Services\WordPress\Exceptions\WordPressRequestException;

final class TokenController extends Controller
{
    public function __construct(private readonly SdkContract $sdk)
    {
    }

    public function __invoke(StoreWpTokenRequest $request): JsonResponse
    {
        $credentials = $request->validated();

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
                ], static fn ($value) => $value !== null),
                data: null,
                status: $sourceStatus ?? 502
            );
        }

        $token = WpToken::create([
            'username' => $credentials['username'],
            'token' => $response['token'] ?? '',
            'payload' => $response,
        ]);

        return ApiResponse::success(
            code: 'wordpress.token_created',
            message: 'Token stored successfully.',
            data: [
                'id' => $token->id,
            ],
            status: 201
        );
    }
}

