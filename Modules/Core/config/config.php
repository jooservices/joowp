<?php

return [
    'name' => 'Core',
    'wordpress' => [
        'base_uri' => env('WORDPRESS_API_BASE_URI', 'https://wordpress.org/wp-json/'),
        'timeout' => (float) env('WORDPRESS_API_TIMEOUT', 10.0),
        'user_agent' => env('WORDPRESS_API_USER_AGENT', 'CoreWordPressSdk/1.0'),
        'namespace' => env('WORDPRESS_API_NAMESPACE', 'wp/v2'),
    ],
];
