<?php

test('Config is loaded', function () {
    $config = config('squiz');

    expect($config)->toBeArray()
        ->toHaveKey('token')
        ->toHaveKey('polling_interval')
        ->toHaveKey('storage_path')
        ->toHaveKey('route_path')
        ->toHaveKey('title')
        ->toHaveKey('heading')
        ->toHaveCount(6);
});
