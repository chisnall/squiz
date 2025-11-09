<?php

use function Pest\Laravel\get;

beforeEach(function () {
    Config::set('squiz.heading', 'Squiz Debugger');

    $this->squizRoute = config('squiz.route_path');
});

test('Squiz view is rendered - no token', function () {
    Config::set('squiz.token');

    get($this->squizRoute)
        ->assertOk()
        ->assertSee('Squiz Debugger');
});

test('Squiz view is rendered - token', function () {
    $token = 'token_here';

    Config::set('squiz.token', $token);

    $this->squizRoute .= "?token=$token";

    get($this->squizRoute)
        ->assertOk()
        ->assertSee('Squiz Debugger');
});
