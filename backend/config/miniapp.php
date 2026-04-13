<?php

return [
    'telegram_origin' => rtrim((string) env('MINIAPP_ORIGIN_TELEGRAM', 'https://tg.richmn.com'), '/'),
    'max_origin' => rtrim((string) env('MINIAPP_ORIGIN_MAX', 'https://max.richmn.com'), '/'),
];
