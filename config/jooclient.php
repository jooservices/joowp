<?php

return [
    /*
    |--------------------------------------------------------------------------
    | JOO Client configuration
    |--------------------------------------------------------------------------
    |
    | This file contains configuration for the jooclient package. It is meant
    | to be published to a Laravel application's config directory (typically via
    | a service provider using `php artisan vendor:publish`).
    |
    | Use environment variables to override defaults.
    |
    */

    // High-level logging switch and driver selection
    'logging' => [
        // Master switch: Enable all logging features in the package
        // When false, no logging is wired up regardless of individual driver settings
        'enabled' => env('JOOCLIENT_LOGGING_ENABLED', true),

        // Sensitive data sanitization
        'sanitize_enabled' => env('JOOCLIENT_SANITIZE_ENABLED', true),
        'sanitize_headers' => array_filter(explode(',', env('JOOCLIENT_SANITIZE_HEADERS', ''))),
        'sanitize_fields' => array_filter(explode(',', env('JOOCLIENT_SANITIZE_FIELDS', ''))),

        // Log level filtering (only log messages at or above this level)
        'min_level' => env('JOOCLIENT_LOG_MIN_LEVEL', 'debug'), // debug, info, notice, warning, error, critical, alert, emergency

        // Log sampling (reduce log volume by only logging a percentage of messages)
        'sample_rate' => env('JOOCLIENT_LOG_SAMPLE_RATE', 1.0), // 1.0 = 100%, 0.1 = 10%

        // Performance metrics (automatically track request duration, memory usage)
        'performance_metrics' => env('JOOCLIENT_LOG_PERFORMANCE_METRICS', true),

        // Structured metadata (add structured metadata to log context)
        'structured_metadata' => env('JOOCLIENT_LOG_STRUCTURED_METADATA', true),

        // Logging driver selection:
        // - 'auto' (default): Automatically detects which drivers are enabled and uses them
        // - 'mysql', 'mongodb', 'monolog': Force specific single driver
        // - 'multi': Force multi-logger mode (uses multi_drivers config)
        // - 'conditional': Conditional routing based on level/status (uses routing config)
        'driver' => env('JOOCLIENT_LOGGING_DRIVER', 'mongodb'),

        // Conditional routing configuration (used when driver='conditional')
        // Example:
        // 'routing' => [
        //     'default' => ['monolog'],
        //     'warning' => ['monolog', 'mysql'],
        //     'error' => ['monolog', 'mysql'],
        //     'critical' => ['monolog', 'mysql', 'sentry'],
        //     'status_codes' => [
        //         '4xx' => ['monolog', 'mysql'],
        //         '5xx' => ['monolog', 'mysql', 'sentry'],
        //     ],
        // ],
        'routing' => [
            // Default: All logs go to these loggers
            'default' => env('JOOCLIENT_LOG_ROUTING_DEFAULT', '')
                ? array_filter(explode(',', env('JOOCLIENT_LOG_ROUTING_DEFAULT', '')))
                : [],

            // Level-based routing: route specific levels to specific loggers
            'warning' => env('JOOCLIENT_LOG_ROUTING_WARNING', '')
                ? array_filter(explode(',', env('JOOCLIENT_LOG_ROUTING_WARNING', '')))
                : [],
            'error' => env('JOOCLIENT_LOG_ROUTING_ERROR', '')
                ? array_filter(explode(',', env('JOOCLIENT_LOG_ROUTING_ERROR', '')))
                : [],
            'critical' => env('JOOCLIENT_LOG_ROUTING_CRITICAL', '')
                ? array_filter(explode(',', env('JOOCLIENT_LOG_ROUTING_CRITICAL', '')))
                : [],

            // Status code routing: route based on HTTP status codes
            'status_codes' => [
                '4xx' => env('JOOCLIENT_LOG_ROUTING_4XX', '')
                    ? array_filter(explode(',', env('JOOCLIENT_LOG_ROUTING_4XX', '')))
                    : [],
                '5xx' => env('JOOCLIENT_LOG_ROUTING_5XX', '')
                    ? array_filter(explode(',', env('JOOCLIENT_LOG_ROUTING_5XX', '')))
                    : [],
            ],
        ],

        // DEPRECATED: Only used when driver is explicitly set to 'multi'
        // With 'auto' mode, just enable individual drivers and it works automatically
        'multi_drivers' => env('JOOCLIENT_MULTI_DRIVERS')
            ? explode(',', env('JOOCLIENT_MULTI_DRIVERS'))
            : [],

        // Connection-specific options: e.g. logging.connection.mysql or logging.connection.mongodb
        'connection' => [
            'mysql' => [
                // Enable or disable DB logging for this connection
                'enabled' => env('JOOCLIENT_DB_LOGGING', false),

                // DB connection driver (mysql)
                'connection' => env('JOOCLIENT_DB_CONNECTION', 'mysql'),

                // MySQL connection details
                'host' => env('JOOCLIENT_DB_HOST', '127.0.0.1'),
                'port' => env('JOOCLIENT_DB_PORT', 3306),
                'database' => env('JOOCLIENT_DB_DATABASE', 'jooclient'),
                'username' => env('JOOCLIENT_DB_USERNAME', 'root'),
                'password' => env('JOOCLIENT_DB_PASSWORD', 'root'),

                // Table where request logs are written
                'table' => env('JOOCLIENT_DB_TABLE', 'client_request_logs'),

                // Whether to batch writes and flush at end of script/test
                'batch' => env('JOOCLIENT_DB_BATCH', false),

                // Fallback strategy when DB writes fail: 'error_log', 'throw', or 'silent'
                'fallback' => env('JOOCLIENT_DB_FALLBACK', 'error_log'),
            ],

            // MongoDB connection settings
            'mongodb' => [
                'enabled' => env('JOOCLIENT_MONGODB_LOGGING', true),
                'dsn' => env('JOOCLIENT_MONGODB_DSN', env('MONGODB_DSN', 'mongodb://127.0.0.1:27017')),
                'database' => env('JOOCLIENT_MONGODB_DATABASE', env('MONGODB_DATABASE', 'jooclient')),
                'collection' => env('JOOCLIENT_MONGODB_COLLECTION', 'client_request_logs'),
                'batch' => env('JOOCLIENT_MONGODB_BATCH', false),
                'fallback' => env('JOOCLIENT_MONGODB_FALLBACK', 'error_log'),
                'options' => [
                    // Additional MongoDB client options can be added here
                    // See: https://www.php.net/manual/en/mongodb-driver-manager.construct.php
                ],

                // File rotation settings (for error/failure logs)
                'file_path' => env('JOOCLIENT_MONGODB_LOG_PATH', storage_path('logs/mongodb_errors.log')),
                'rotate_size' => env('JOOCLIENT_MONGODB_ROTATE_SIZE', 10485760), // 10MB
                'rotate_files' => env('JOOCLIENT_MONGODB_ROTATE_FILES', 5), // Keep 5 rotated files
            ],

            // Monolog connection settings
            'monolog' => [
                'enabled' => env('JOOCLIENT_MONOLOG_LOGGING', false),

                // Storage configuration
                'path' => env('JOOCLIENT_MONOLOG_PATH', storage_path('logs')),
                'filename' => env('JOOCLIENT_MONOLOG_FILENAME', 'jooclient.log'),

                // File rotation settings
                'rotate_enabled' => env('JOOCLIENT_MONOLOG_ROTATE_ENABLED', false), // Set to true to enable daily rotation
                'rotate_max_files' => env('JOOCLIENT_MONOLOG_ROTATE_MAX_FILES', 7), // Keep 7 days (when rotation enabled)

                // Logging configuration
                'channel' => env('JOOCLIENT_MONOLOG_CHANNEL', 'jooclient'),
                'level' => env('JOOCLIENT_MONOLOG_LEVEL', 'info'), // debug, info, warning, error
                'formatter' => env('JOOCLIENT_MONOLOG_FORMATTER', null), // null or 'json'
            ],
        ],
    ],

    // NOTE: DB logging settings moved under logging.connection.mysql for clearer driver/connection separation.

    'retries' => [
        'enabled' => env('JOOCLIENT_RETRIES', true),
        'max_attempts' => env('JOOCLIENT_RETRIES_MAX', 3),
        'delay_seconds' => env('JOOCLIENT_RETRIES_DELAY', 1),
        'min_error_code' => env('JOOCLIENT_RETRIES_MIN_ERROR_CODE', 500),
    ],

    // Caching Configuration
    'cache' => [
        'enabled' => env('JOOCLIENT_CACHE_ENABLED', false),
        'driver' => env('JOOCLIENT_CACHE_DRIVER', 'redis'), // 'redis' or 'filesystem'
        'default_ttl' => env('JOOCLIENT_CACHE_TTL', 3600), // 1 hour default

        // Redis cache settings
        'redis' => [
            'host' => env('JOOCLIENT_REDIS_HOST', env('REDIS_HOST', '127.0.0.1')),
            'port' => env('JOOCLIENT_REDIS_PORT', env('REDIS_PORT', 6379)),
            'password' => env('JOOCLIENT_REDIS_PASSWORD', env('REDIS_PASSWORD', '')),
            'database' => env('JOOCLIENT_REDIS_DATABASE', 0),
            'timeout' => env('JOOCLIENT_REDIS_TIMEOUT', 5),
            'prefix' => env('JOOCLIENT_REDIS_PREFIX', 'jooclient:'),
        ],

        // Filesystem cache settings
        'filesystem' => [
            'path' => env('JOOCLIENT_CACHE_PATH', storage_path('framework/cache/jooclient')),
            'file_permissions' => 0644,
            'directory_permissions' => 0755,
        ],
    ],

    // Rate limiting defaults
    'rate_limit' => [
        'enabled' => env('JOOCLIENT_RATE_LIMIT_ENABLED', false),
        'strategy' => env('JOOCLIENT_RATE_LIMIT_STRATEGY', 'token_bucket'), // token_bucket, sliding_window, fixed_window
        'requests_per_second' => env('JOOCLIENT_RATE_LIMIT_RPS', 10),
        'burst_size' => env('JOOCLIENT_RATE_LIMIT_BURST', 20),
        'per_domain' => env('JOOCLIENT_RATE_LIMIT_PER_DOMAIN', true),
    ],

    // Circuit breaker defaults
    'circuit_breaker' => [
        'enabled' => env('JOOCLIENT_CIRCUIT_BREAKER_ENABLED', false),
        'failure_threshold' => env('JOOCLIENT_CB_FAILURE_THRESHOLD', 5),
        'timeout' => env('JOOCLIENT_CB_TIMEOUT', 60),
        'half_open_max_calls' => env('JOOCLIENT_CB_HALF_OPEN_MAX', 3),
        'per_domain' => env('JOOCLIENT_CB_PER_DOMAIN', true),
    ],

    // Client-level defaults
    'defaults' => [
        'timeout' => env('JOOCLIENT_TIMEOUT', 30), // Increased from 10 to 30 seconds
        'connect_timeout' => env('JOOCLIENT_CONNECT_TIMEOUT', 10),
        'read_timeout' => env('JOOCLIENT_READ_TIMEOUT', 30),
        'headers' => [
            'Accept' => env('JOOCLIENT_ACCEPT_HEADER', 'application/json'),
            'Accept-Encoding' => 'gzip, deflate',
        ],
        'version' => env('JOOCLIENT_HTTP_VERSION', '1.1'),
        'allow_redirects' => [
            'max' => env('JOOCLIENT_MAX_REDIRECTS', 5),
            'strict' => false,
            'referer' => true,
            'protocols' => ['http', 'https'],
        ],
        'verify' => env('JOOCLIENT_VERIFY_SSL', true),
        'http_errors' => true, // Throw exceptions on 4xx/5xx
    ],

    // Environment-specific presets
    'presets' => [
        'development' => [
            'timeout' => 60,
            'verify' => false, // Allow self-signed certs in dev
            'logging' => ['enabled' => true],
            'request_history' => true,
        ],
        'production' => [
            'timeout' => 30,
            'verify' => true,
            'logging' => ['enabled' => false], // Disable by default in prod
            'rate_limit' => ['enabled' => true],
            'circuit_breaker' => ['enabled' => true],
        ],
        'testing' => [
            'timeout' => 5,
            'verify' => false,
            'logging' => ['enabled' => false],
            'retries' => ['enabled' => false],
        ],
    ],
];
