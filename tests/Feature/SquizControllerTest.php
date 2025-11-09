<?php

use Chisnall\Squiz\Http\Controllers\SquizController;

beforeEach(function () {
    // Instantiate controller
    $this->squizController = new SquizController;

    // Create squiz directory
    $this->tempSquizDirectory = storage_path().'/squiz';
    mkdir($this->tempSquizDirectory);

    // Point controller to our temp directory
    $this->squizController->logPath = $this->tempSquizDirectory;

    // Set first log ID
    $id = (int) (microtime(true) * 1_000_000);

    // Generates IDs
    for ($i = 0; $i < 3; $i++) {
        $this->tempSquizFiles[] = $id + $i;
    }

    // Create files
    foreach ($this->tempSquizFiles as $id) {
        $file = $this->tempSquizDirectory.'/'.$id.'.log';

        $output = [
            'id' => $id,
            'datetime' => now()->format('Y-m-d H:i:s'),
            'file' => '/example/file',
            'line' => 17,
            'comment' => null,
            'terminated' => false,
            'entry' => null,
        ];

        file_put_contents($file, serialize($output));
    }
});

test('Constructor - disable debugbar', function () {
    $mock = Mockery::mock();
    $mock->shouldReceive('disable')->once();
    $mock->shouldReceive('isEnabled')->andReturn(false);

    app()->instance('debugbar', $mock);

    new SquizController;

    expect(app('debugbar')->isEnabled())->toBeFalse();
});

test('Constructor - log path is set', function () {
    $this->squizController = new SquizController;

    $logPath = config('squiz.storage_path').'/squiz';

    expect($this->squizController->logPath)->toBe($logPath);
});

test('Constructor - log path is created', function () {
    // Change squiz directory to a different one to confirm the directory is automatically created
    $logPath = storage_path().'/squiz-alt';

    $this->squizController = new SquizController($logPath);

    expect(file_exists($this->squizController->logPath))->toBeTrue();
});

test('index()', function () {
    $result = $this->squizController->index();

    $viewPath = base_path('vendor/chisnall/squiz/src/Views/squiz.blade.php');

    expect($result)->toBeInstanceOf(Illuminate\View\View::class)
        ->and($result->getPath())->toBe($viewPath);
});

test('files()', function () {
    $files = $this->squizController->files();

    $paths = collect($files)->map->getPathname()->all();

    expect($files)->toBeArray()
        ->and(count($files))->toBe(3);

    foreach ($this->tempSquizFiles as $id) {
        expect($paths)->toContain($this->tempSquizDirectory.'/'.$id.'.log');
    }
});

test('logIds()', function () {
    // Get files and remove the first one to test the catch block
    $files = $this->squizController->files();
    $path = $files[0]->getRealPath();
    unlink($path);

    unset($this->tempSquizFiles[0]);

    $result = $this->squizController->logIds($files);

    expect($result)->toBeArray()
        ->and(count($result))->toBe(2);

    foreach ($this->tempSquizFiles as $id) {
        expect($result)->toContain($id);
    }
});

test('logEntries()', function () {
    // Get files and remove the first one to test the catch block
    $files = $this->squizController->files();
    $path = $files[0]->getRealPath();
    unlink($path);

    unset($this->tempSquizFiles[0]);

    $result = $this->squizController->logEntries($files);

    expect($result)->toBeArray()
        ->and(count($result))->toBe(2);

    foreach ($this->tempSquizFiles as $id) {
        expect($result)->toHaveKey($id);
    }
});

test('getLogIds()', function () {
    $result = $this->squizController->getLogIds();

    expect($result)->toBeInstanceOf(\Illuminate\Http\JsonResponse::class)
        ->and(count($result->getData()))->toBe(3);

    foreach ($this->tempSquizFiles as $id) {
        expect($result->getData())->toContain($id);
    }
});

test('getLogEntries()', function () {
    $request = Request::create(uri: config('squiz.route_path'), parameters: ['logIds' => $this->tempSquizFiles]);

    $result = $this->squizController->getLogEntries($request);

    // $resultIds = array_map(fn($item) => $item->id, $result->getData());
    $resultIds = array_column($result->getData(), 'id');

    expect($result)->toBeInstanceOf(\Illuminate\Http\JsonResponse::class)
        ->and(count($result->getData()))->toBe(3);

    foreach ($this->tempSquizFiles as $id) {
        expect($resultIds)->toContain($id);
    }
});

test('clearLog()', function () {
    $result = $this->squizController->clearLog();

    $files = $this->squizController->files();

    expect($result)->toBeInstanceOf(\Illuminate\Http\JsonResponse::class)
        ->and(count($files))->toBe(0)
        ->and($result->getData()->message)->toBe('Log cleared');
});

test('deleteEntry()', function () {
    $firstId = $this->tempSquizFiles[0];

    $firstLog = "$this->tempSquizDirectory/$firstId.log";

    $request = Request::create(uri: config('squiz.route_path'), parameters: ['entryId' => $firstId]);

    $result = $this->squizController->deleteEntry($request);

    $files = $this->squizController->files();

    $paths = collect($files)->map->getPathname()->all();

    expect($result)->toBeInstanceOf(\Illuminate\Http\JsonResponse::class)
        ->and(count($files))->toBe(2)
        ->and($paths)->not()->toContain($firstLog)
        ->and($result->getData()->message)->toBe("Entry deleted: $firstId");
});
