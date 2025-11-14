<?php

declare(strict_types=1);

return [
    'lmstudio' => [
        'enabled' => (bool) env('FEATURE_LM_STUDIO_ENABLED', env('APP_ENV') === 'local'),
    ],
];
