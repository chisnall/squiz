<?php

beforeEach(function () {
    // Set token to null
    Config::set('squiz.token');
});

test('Token middleware is registered', function () {
    $routes = ['squizIndex', 'squizClear', 'squizDelete', 'squizEntries', 'squizIds'];

    foreach ($routes as $name) {
        $route = Route::getRoutes()->getByName($name);

        $middleware = $route->gatherMiddleware();

        expect($route)->not()->toBeNull()
            ->and($middleware)->toContain('squizToken');
    }
});

test('Token middleware - local environment', function () {
    $this->get(config('squiz.route_path'))->assertOk();
});

test('Token middleware - production environment - token not set', function () {
    App::detectEnvironment(fn () => 'production');

    $this->get(config('squiz.route_path'))->assertOk();
});

test('Token middleware - production environment - token is incorrect', function () {
    App::detectEnvironment(fn () => 'production');

    $token = 'token_here';

    Config::set('squiz.token', 'different_token');

    $this->get(config('squiz.route_path')."?token=$token")->assertNotFound();
});

test('Token middleware - token is correct - URL query string', function () {
    App::detectEnvironment(fn () => 'production');

    $token = 'token_here';

    Config::set('squiz.token', $token);

    $this->get(config('squiz.route_path')."?token=$token")->assertOk();
});

test('Token middleware - token is correct - request header', function () {
    App::detectEnvironment(fn () => 'production');

    $token = 'token_here';

    Config::set('squiz.token', $token);

    $this->withHeaders(['X-SQUIZ-TOKEN' => $token])
        ->get(config('squiz.route_path'))->assertOk();
});
