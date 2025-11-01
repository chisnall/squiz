<?php

beforeEach(function () {
    // Get log entries before in the system storage path
    $this->filesBefore = collect(File::files("$this->systemStoragePath/squiz"))->map->getPathname();
});

afterEach(function () {
    // Get log entries after in the system storage path
    $filesAfter = collect(File::files("$this->systemStoragePath/squiz"))->map->getPathname();

    // Determine new files in the system storage path
    $newFiles = $filesAfter->diff($this->filesBefore);

    // Delete new files
    foreach ($newFiles as $file) {
        unlink($file);
    }
});

test('deleteDirectory()', function () {
    // Go multi-level with the directories and files
    $tempDirectory1 = '/tmp/deleteDirectoryTest';
    $tempDirectory2 = '/tmp/deleteDirectoryTest/level2';
    $tempDirectory3 = '/tmp/deleteDirectoryTest/level2/level3';

    mkdir(directory: $tempDirectory3, recursive: true);
    touch("$tempDirectory1/file1.txt");
    touch("$tempDirectory2/file2.txt");
    touch("$tempDirectory3/file3.txt");

    $result = deleteDirectory($tempDirectory1);

    expect($result)->toBeTrue()
        ->and(file_exists($tempDirectory1))->toBeFalse();
});

test('deleteDirectory() handles exception - directory does not exist', function () {
    $directory = '/tmp/directory-does-not-exist';

    expect(fn() => deleteDirectory($directory))
        ->toThrow(Exception::class, 'Directory does not exist');
});

test('deleteDirectory() handles exception - path is not a directory', function () {
    $file = '/tmp/this-is-a-file-not-a-directory';

    touch($file);

    expect(fn() => deleteDirectory($file))
        ->toThrow(Exception::class, 'Path is not a directory');

    unlink($file);
});

test('deleteDirectory() handles exception - permission issue (directory)', function () {
    // This directory is not writable, therefore cannot be removed
    $directory = '/proc/tty/ldisc';

    expect(fn() => deleteDirectory($directory))
        ->toThrow(Exception::class, 'Permission denied');
});

test('deleteDirectory() handles exception - permission issue (file)', function () {
    // This directory includes a file that is not writable, therefore cannot be removed
    $directory = '/proc/driver';

    expect(fn() => deleteDirectory($directory))
        ->toThrow(Exception::class, 'Permission denied');
});

test('squiz()', function () {
    $result = squiz('Test squiz() from Pest');

    expect($result)->toBeTrue();
});

test('squizd()', function () {
    $result = squizd('Test squizd() from Pest', '__PEST_TEST__');

    expect($result)->toBeTrue();
});

test('squiz() running on production environment', function () {
    App::shouldReceive('environment')->andReturn('production');

    $result = squiz('Test squiz() from Pest');

    expect($result)->toBeFalse();
});

test('squiz() where storage path is not valid', function () {
    app()->useStoragePath('/tmp/directory-is-missing');

    expect(fn() => squiz('Test squiz() from Pest'))
        ->toThrow(Exception::class, 'Storage path does not exist');
});

test('squiz() fails to creates the squiz directory', function () {
    // Create file to stop the mkdir() function from working in the squiz() function
    touch("$this->tempStoragePath/squiz");

    expect(fn() => squiz('Test squiz() from Pest'))
        ->toThrow(Exception::class, 'Failed to create squiz subdirectory');
});

test('squiz() detects a view', function () {
    // We need to switch to using the standard storage path here due where the compiled views are stored
    app()->useStoragePath($this->systemStoragePath);

    // Note - this also tests @ld and @ldd directives
    $view = view('directives.squiz');

    // Render the views
    expect($view->render())->toContain('Testing view detected');
});

test('runningInPest() - true', function () {
    $fakeBacktrace = [
        ['class' => \Tests\TestCase::class],
    ];

    expect(runningInPest($fakeBacktrace))->toBeTrue();
});

test('runningInPest() - false 1', function () {
    $fakeBacktrace = [
        ['class' => \App\Http\Controllers\Controller::class],
    ];

    expect(runningInPest($fakeBacktrace))->toBeFalse();
});

test('runningInPest() - false 2', function () {
    $fakeBacktrace = [
        ['file' => 'test_file'],
    ];

    expect(runningInPest($fakeBacktrace))->toBeFalse();
});
