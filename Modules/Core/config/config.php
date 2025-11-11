<?php

$baseUrl = rtrim(env('WP_URL', 'https://soulevil.com'), '/');

return [
    'name' => 'Core',
    'wordpress' => [
        'base_uri' => $baseUrl.'/wp-json/',
        'timeout' => (float) env('WORDPRESS_API_TIMEOUT', 10.0),
        'user_agent' => env('WORDPRESS_API_USER_AGENT', 'CoreWordPressSdk/1.0'),
        'namespace' => env('WORDPRESS_API_NAMESPACE', 'wp/v2'),
    ],
];
