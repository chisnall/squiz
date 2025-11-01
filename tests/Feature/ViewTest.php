<?php

use Illuminate\View\Compilers\BladeCompiler;

use function Pest\Laravel\get;

test('Squiz view is rendered', function () {
    Config::set('squiz.title', 'Squiz Debugger');

    $squizRoute = config('squiz.route_path');

    get($squizRoute)
        ->assertOk()
        ->assertSee('Squiz Debugger');
});
