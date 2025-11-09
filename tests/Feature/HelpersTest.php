<?php

use Illuminate\Support\Facades\App;

beforeEach(function () {
    // Test array
    $this->testArray = [
        'id' => 1,
        'name' => 'David Banner',
        'email' => 'david@email.com',
        'active' => true,
    ];

    // Test object
    $this->testObject = (object) [
        'id' => 2,
        'name' => 'Arya Stark',
        'email' => 'arya@email.com',
        'active' => false,
    ];

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

function getLogEntry(string $path): array
{
    // Get last entry
    $file = collect(File::files("$path/squiz"))->last()->getPathname();

    // Read file and unserialize
    $contents = File::get($file);
    $entry = unserialize($contents);

    return $entry;
}

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

    expect(fn () => deleteDirectory($directory))
        ->toThrow(Exception::class, 'Directory does not exist');
});

test('deleteDirectory() handles exception - path is not a directory', function () {
    $file = '/tmp/this-is-a-file-not-a-directory';

    touch($file);

    expect(fn () => deleteDirectory($file))
        ->toThrow(Exception::class, 'Path is not a directory');

    unlink($file);
});

test('deleteDirectory() handles exception - permission issue (directory)', function () {
    // This directory is not writable, therefore cannot be removed
    $directory = '/proc/tty/ldisc';

    expect(fn () => deleteDirectory($directory))
        ->toThrow(Exception::class, 'Permission denied');
});

test('deleteDirectory() handles exception - permission issue (file)', function () {
    // This directory includes a file that is not writable, therefore cannot be removed
    $directory = '/proc/driver';

    expect(fn () => deleteDirectory($directory))
        ->toThrow(Exception::class, 'Permission denied');
});

test('squiz() running on production environment', function () {
    App::detectEnvironment(fn () => 'production');

    $result = squiz('Test squiz() from Pest');

    expect($result)->toBeFalse();
});

test('squizd() running on production environment', function () {
    App::detectEnvironment(fn () => 'production');

    $result = squizd('Test squizd() from Pest');

    expect($result)->toBeFalse();
});

test('squiz() where storage path is not valid', function () {
    app()->useStoragePath('/tmp/directory-is-missing');

    expect(fn () => squiz('Test squiz() from Pest'))
        ->toThrow(Exception::class, 'Storage path does not exist');
});

test('squiz() fails to creates the squiz directory', function () {
    // Create file to stop the mkdir() function from working in the squiz() function
    touch("$this->tempStoragePath/squiz");

    expect(fn () => squiz('Test squiz() from Pest'))
        ->toThrow(Exception::class, 'Failed to create squiz subdirectory');
});

test('squiz() single entry - string', function () {
    $result = squiz('This is a test string');

    $log = getLogEntry($this->systemStoragePath);

    $entry = base64_decode($log['entry']);

    expect($result)->toBeTrue()
        ->and($log)->toBeArray()
        ->and($log)->toHaveCount(7)
        ->and($entry)->toContain('>This is a test string<');
});

test('squiz() single entry - integer', function () {
    $result = squiz(299792458);

    $log = getLogEntry($this->systemStoragePath);

    $entry = base64_decode($log['entry']);

    expect($result)->toBeTrue()
        ->and($log)->toBeArray()
        ->and($log)->toHaveCount(7)
        ->and($entry)->toContain('>299792458<');
});

test('squiz() single entry - float', function () {
    $result = squiz(3.14159265359);

    $log = getLogEntry($this->systemStoragePath);

    $entry = base64_decode($log['entry']);

    expect($result)->toBeTrue()
        ->and($log)->toBeArray()
        ->and($log)->toHaveCount(7)
        ->and($entry)->toContain('>3.14159265359<');
});

test('squiz() single entry - array', function () {
    $result = squiz($this->testArray);

    $log = getLogEntry($this->systemStoragePath);

    $entry = base64_decode($log['entry']);

    expect($result)->toBeTrue()
        ->and($log)->toBeArray()
        ->and($log)->toHaveCount(7)
        ->and($entry)->toContain('>David Banner<');
});

test('squiz() single entry - object', function () {
    $result = squiz($this->testObject);

    $log = getLogEntry($this->systemStoragePath);

    $entry = base64_decode($log['entry']);

    expect($result)->toBeTrue()
        ->and($log)->toBeArray()
        ->and($log)->toHaveCount(7)
        ->and($entry)->toContain('>Arya Stark<');
});

test('squizd() single entry - string', function () {
    $result = squizd('This is a test string');

    $log = getLogEntry($this->systemStoragePath);

    $entry = base64_decode($log['entry']);

    expect($result)->toBeTrue()
        ->and($log)->toBeArray()
        ->and($log)->toHaveCount(7)
        ->and($entry)->toContain('>This is a test string<');
});

test('squizd() single entry - integer', function () {
    $result = squizd(299792458);

    $log = getLogEntry($this->systemStoragePath);

    $entry = base64_decode($log['entry']);

    expect($result)->toBeTrue()
        ->and($log)->toBeArray()
        ->and($log)->toHaveCount(7)
        ->and($entry)->toContain('>299792458<');
});

test('squizd() single entry - float', function () {
    $result = squizd(3.14159265359);

    $log = getLogEntry($this->systemStoragePath);

    $entry = base64_decode($log['entry']);

    expect($result)->toBeTrue()
        ->and($log)->toBeArray()
        ->and($log)->toHaveCount(7)
        ->and($entry)->toContain('>3.14159265359<');
});

test('squizd() single entry - array', function () {
    $result = squizd($this->testArray);

    $log = getLogEntry($this->systemStoragePath);

    $entry = base64_decode($log['entry']);

    expect($result)->toBeTrue()
        ->and($log)->toBeArray()
        ->and($log)->toHaveCount(7)
        ->and($entry)->toContain('>David Banner<');
});

test('squizd() single entry - object', function () {
    $result = squizd($this->testObject);

    $log = getLogEntry($this->systemStoragePath);

    $entry = base64_decode($log['entry']);

    expect($result)->toBeTrue()
        ->and($log)->toBeArray()
        ->and($log)->toHaveCount(7)
        ->and($entry)->toContain('>Arya Stark<');
});

test('squiz() multiple entries', function () {
    $result = squiz('This is a test string', $this->testArray, $this->testObject);

    $log = getLogEntry($this->systemStoragePath);

    $entry = base64_decode($log['entry']);

    expect($result)->toBeTrue()
        ->and($log)->toBeArray()
        ->and($log)->toHaveCount(7)
        ->and($entry)->toContain('>This is a test string<')
        ->and($entry)->toContain('>David Banner<')
        ->and($entry)->toContain('>Arya Stark<');
});

test('squizd() multiple entries', function () {
    $result = squizd('This is a test string', $this->testArray, $this->testObject);

    $log = getLogEntry($this->systemStoragePath);

    $entry = base64_decode($log['entry']);

    expect($result)->toBeTrue()
        ->and($log)->toBeArray()
        ->and($log)->toHaveCount(7)
        ->and($entry)->toContain('>This is a test string<')
        ->and($entry)->toContain('>David Banner<')
        ->and($entry)->toContain('>Arya Stark<');
});

test('squiz() handles a comment', function () {
    $result = squiz('Test squiz() from Pest', 'comment:this is a comment');

    $log = getLogEntry($this->systemStoragePath);

    expect($result)->toBeTrue()
        ->and($log)->toBeArray()
        ->and($log['comment'])->toBe('this is a comment');
});

test('squiz() detects a view', function () {
    // We need to switch to using the standard storage path here due where the compiled views are stored
    app()->useStoragePath($this->systemStoragePath);

    // Note - this also tests @ld and @ldd directives
    $view = view('directives.squiz');

    // Render the views
    expect($view->render())->toContain('<p>Testing view detected in squiz()</p>');
});

test('squiz() backtrace alias', function () {
    $result = squiz('Test squiz() from Pest', '__backtrace__');

    $log = getLogEntry($this->systemStoragePath);

    $entry = base64_decode($log['entry']);

    expect($result)->toBeTrue()
        ->and($log)->toBeArray()
        ->and($entry)->toContain('tests/Feature/HelpersTest.php');
});

test('squizd() backtrace alias', function () {
    $result = squizd('Test squiz() from Pest', '__backtrace__');

    $log = getLogEntry($this->systemStoragePath);

    $entry = base64_decode($log['entry']);

    expect($result)->toBeTrue()
        ->and($log)->toBeArray()
        ->and($entry)->toContain('tests/Feature/HelpersTest.php');
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
