<?php

use Illuminate\View\Compilers\BladeCompiler;

test('squiz directive compiles correctly', function () {
    $blade = app(BladeCompiler::class);

    $compiled = $blade->compileString("@squiz('data')");

    expect($compiled)->toContain("<?php squiz('data'); ?>");
});

test('squizd directive compiles correctly', function () {
    $blade = app(BladeCompiler::class);

    $compiled = $blade->compileString("@squizd('data')");

    expect($compiled)->toContain("<?php squizd('data'); ?>");
});
