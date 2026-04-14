<?php

return [
    'model' => env('IMAGEGEN_MODEL', 'gpt-image-1'),
    'size' => '1024x1024',
    'quality' => 'medium',
    'icon_size' => 256,
    'references_path' => storage_path('app/icon-references'),
];
