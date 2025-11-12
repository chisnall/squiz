<?php

test('Config is loaded', function () {
    $config = config('squiz');

    expect($config)->toBeArray()
        ->and($config)->toHaveKeys(['token', 'polling_interval', 'storage_path', 'route_path', 'title', 'heading'])
        ->and($config)->toHaveCount(6);
});
