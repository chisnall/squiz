<?php

return [

    'token' => env('SQUIZ_TOKEN'),
    'polling_interval' => env('SQUIZ_POLLING_INTERVAL', 1000),
    'route_path' => env('SQUIZ_ROUTE_PATH', '/squiz'),
    'heading' => env('SQUIZ_HEADING', 'Squiz'),

];
