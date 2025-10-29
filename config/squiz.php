<?php

return [

    'token' => env('SQUIZ_TOKEN'),
    'polling_interval' => env('SQUIZ_POLLING_INTERVAL', 1000),
    'storage_path' => env('SQUIZ_STORAGE_PATH', storage_path()),
    'route_path' => env('SQUIZ_ROUTE_PATH', '/squiz'),
    'title' => env('SQUIZ_TITLE', 'Squiz'),
    'heading' => env('SQUIZ_HEADING', 'Squiz'),

];
