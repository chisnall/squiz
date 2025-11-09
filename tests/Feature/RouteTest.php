<?php

test('Web routes are loaded', function () {
    expect(Route::has('squizIndex'))->toBeTrue();
});

test('API routes are loaded', function () {
    expect(Route::has('squizClear'))->toBeTrue();
    expect(Route::has('squizDelete'))->toBeTrue();
    expect(Route::has('squizEntries'))->toBeTrue();
    expect(Route::has('squizIds'))->toBeTrue();
});
