<?php

declare(strict_types=1);

$baseUrl = rtrim((string) env('WP_URL', 'https://soulevil.com'), '/');
$timeout = env('WORDPRESS_API_TIMEOUT');
$namespace = (string) env('WORDPRESS_API_NAMESPACE', 'wp/v2');
$userAgent = (string) env('WORDPRESS_API_USER_AGENT', 'CoreWordPressSdk/1.0');

return [
    'name' => 'WordPress',
    'api' => [
        'base_uri' => $baseUrl . '/wp-json/',
        'timeout' => is_numeric($timeout) ? (float) $timeout : 10.0,
        'user_agent' => $userAgent ?: 'CoreWordPressSdk/1.0',
        'namespace' => $namespace ?: 'wp/v2',
    ],
];
