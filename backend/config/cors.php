<?php

$origins = array_filter([
    rtrim((string) env('MINIAPP_ORIGIN_TELEGRAM', 'https://tg.richmn.com'), '/'),
    rtrim((string) env('MINIAPP_ORIGIN_MAX', 'https://max.richmn.com'), '/'),
]);

$extra = env('CORS_EXTRA_ORIGINS');
if ($extra === null) {
    $extras = ['http://localhost:5173', 'https://richmn.test'];
} else {
    $extras = $extra === ''
        ? []
        : array_values(array_filter(array_map('trim', explode(',', $extra))));
}

return [
    'paths' => ['api/*'],
    'allowed_methods' => ['*'],
    'allowed_origins' => array_values(array_unique([...$origins, ...$extras])),
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => false,
];
