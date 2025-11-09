<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->extend(Tests\TestCase::class)
    ->use(RefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function something()
{
    // ..
}

/*
|--------------------------------------------------------------------------
| Setup and tear-down
|--------------------------------------------------------------------------
| https://pestphp.com/docs/hooks
*/

pest()->beforeEach(function () {
    // Save the system storage path
    $this->systemStoragePath = storage_path();

    // Set temporary storage path
    $this->tempStoragePath = '/run/pestStorage';

    // Create directory for temporary storage path
    if (! file_exists($this->tempStoragePath)) {
        mkdir($this->tempStoragePath);
    }

    // Set temporary storage path
    app()->useStoragePath($this->tempStoragePath);
});

pest()->afterEach(function () {
    deleteDirectory($this->tempStoragePath);
});
