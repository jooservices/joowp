<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | LM Studio Base URL
    |--------------------------------------------------------------------------
    |
    | The base URL where LM Studio server is accessible. Defaults to loopback.
    | For LAN access, use http://0.0.0.0:1234 or the machine's IP address.
    |
    */
    'base_url' => env('LM_STUDIO_BASE_URL', 'http://127.0.0.1:1234'),

    /*
    |--------------------------------------------------------------------------
    | API Key (Bearer Token)
    |--------------------------------------------------------------------------
    |
    | Optional API key for authentication. Required when LM Studio has
    | "Require API key" enabled. Leave null for unauthenticated access.
    |
    */
    'api_key' => env('LM_STUDIO_API_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Request Timeout
    |--------------------------------------------------------------------------
    |
    | Maximum time (in seconds) to wait for HTTP responses.
    |
    */
    'timeout' => (int) env('LM_STUDIO_TIMEOUT', 30),

    /*
    |--------------------------------------------------------------------------
    | Connection Timeout
    |--------------------------------------------------------------------------
    |
    | Maximum time (in seconds) to wait for connection establishment.
    |
    */
    'connect_timeout' => (int) env('LM_STUDIO_CONNECT_TIMEOUT', 10),

    /*
    |--------------------------------------------------------------------------
    | Max Retries
    |--------------------------------------------------------------------------
    |
    | Number of retry attempts for failed requests before giving up.
    |
    */
    'max_retries' => (int) env('LM_STUDIO_MAX_RETRIES', 2),

    /*
    |--------------------------------------------------------------------------
    | Stream Retry Delay
    |--------------------------------------------------------------------------
    |
    | Delay (in milliseconds) between SSE stream retry attempts.
    |
    */
    'stream_retry_ms' => (int) env('LM_STUDIO_STREAM_RETRY_MS', 250),

    /*
    |--------------------------------------------------------------------------
    | TLS Verification
    |--------------------------------------------------------------------------
    |
    | Verify SSL/TLS certificates. Disable only for local development.
    |
    */
    'verify_tls' => (bool) env('LM_STUDIO_VERIFY_TLS', true),

    /*
    |--------------------------------------------------------------------------
    | Allowed Hosts
    |--------------------------------------------------------------------------
    |
    | Whitelist of hosts allowed for LM Studio connections.
    | Empty array allows all hosts.
    |
    */
    'allowed_hosts' => array_filter(explode(',', env('LM_STUDIO_ALLOWED_HOSTS', '127.0.0.1,localhost'))),

    /*
    |--------------------------------------------------------------------------
    | Default Model
    |--------------------------------------------------------------------------
    |
    | Default model ID for chat/completion requests when not specified.
    |
    */
    'default_model' => env('LM_STUDIO_DEFAULT_MODEL'),

    /*
    |--------------------------------------------------------------------------
    | Default Embedding Model
    |--------------------------------------------------------------------------
    |
    | Default model ID for embedding requests.
    |
    */
    'default_embedding_model' => env('LM_STUDIO_DEFAULT_EMBEDDING_MODEL'),

    /*
    |--------------------------------------------------------------------------
    | Enable Audio Endpoints
    |--------------------------------------------------------------------------
    |
    | Feature flag for audio transcription/translation/speech endpoints.
    | Requires LM Studio >= 0.2.20.
    |
    */
    'enable_audio' => (bool) env('LM_STUDIO_ENABLE_AUDIO', false),

    /*
    |--------------------------------------------------------------------------
    | Enable Image Generation
    |--------------------------------------------------------------------------
    |
    | Feature flag for image generation endpoints.
    | Requires LM Studio >= 0.2.21 with image backend installed.
    |
    */
    'enable_images' => (bool) env('LM_STUDIO_ENABLE_IMAGES', false),

    /*
    |--------------------------------------------------------------------------
    | Enable Streaming
    |--------------------------------------------------------------------------
    |
    | Enable SSE streaming for chat completions. Disable to use buffered mode.
    |
    */
    'enable_streaming' => (bool) env('LM_STUDIO_ENABLE_STREAMING', true),

    /*
    |--------------------------------------------------------------------------
    | Telemetry Log Channel
    |--------------------------------------------------------------------------
    |
    | Channel used for LM Studio outbound request logs. Defaults to "external".
    |
    */
    'log_channel' => env('LM_STUDIO_LOG_CHANNEL', 'external'),
];
