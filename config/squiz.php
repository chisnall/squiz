<?php

return [

    // Token for non-local environments
    'token' => env('SQUIZ_TOKEN'),

    // Polling interval in milliseconds
    'polling_interval' => env('SQUIZ_POLLING_INTERVAL', 1000),

    // System storage path - means logging works in unit tests when switching to a temporary storage path
    'storage_path' => env('SQUIZ_STORAGE_PATH', storage_path()),

    // The path to the Squiz debugging page and API routes
    'route_path' => env('SQUIZ_ROUTE_PATH', '/squiz'),

    // The title of the Squiz debugging page
    'title' => env('SQUIZ_TITLE', 'Squiz'),

    // The heading of the Squiz debugging page
    'heading' => env('SQUIZ_HEADING', 'Squiz'),

];
