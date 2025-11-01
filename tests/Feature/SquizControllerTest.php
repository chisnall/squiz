<?php

use Chisnall\Squiz\Http\Controllers\SquizController;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

beforeEach(function () {
    // Instantiate controller
    $this->squizController = new SquizController();

    // Create squiz directory
    $this->tempSquizDirectory = storage_path() . '/squiz';
    mkdir($this->tempSquizDirectory);

    // Point controller to our temp directory
    $this->squizController->logPath = $this->tempSquizDirectory;

    // Set first log ID
    $id = (int)(microtime(true) * 1_000_000);

    // Generates IDs
    for ($i = 0; $i < 3; $i++) {
        $this->tempSquizFiles[] = $id + $i;
    }

    // Create files
    foreach ($this->tempSquizFiles as $id) {
        $file = $this->tempSquizDirectory . '/' . $id . '.log';

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

test('Constructor - log path is set', function () {
    Config::set('squiz.token', null);

    $controller = new SquizController();

    $logPath = config('squiz.storage_path') . '/squiz';

    expect($controller->logPath)->toBe($logPath);
});

test('Constructor - local environment', function () {
    App::shouldReceive('environment')->andReturn('local');

    $controller = new SquizController();

    expect($controller->tokenValid)->toBeNull();
});

test('Constructor - token not set', function () {
    Config::set('squiz.token', null);

    $controller = new SquizController();

    expect($controller->tokenValid)->toBeTrue();
});

test('Constructor - token is incorrect', function () {
    Config::set('squiz.token', 'token_here');

    expect(fn() => $controller = new SquizController())
        ->toThrow(NotFoundHttpException::class);
});

test('Constructor - token is correct - URL query string', function () {
    $token = 'token_here';

    Config::set('squiz.token', $token);

    $request = Request::create(uri: config('squiz.route_path'), parameters: ['token' => $token]);

    app()->instance('request', $request);

    $controller = new SquizController();

    expect($controller->appToken)->toBe($token)
        ->and($controller->tokenValid)->toBeTrue();
});

test('Constructor - token is correct - request header', function () {
    $token = 'token_here';

    Config::set('squiz.token', $token);

    $request = Request::create(uri: config('squiz.route_path'), server: ['HTTP_X_SQUIZ_TOKEN' => $token]);

    app()->instance('request', $request);

    $controller = new SquizController();

    expect($controller->appToken)->toBe($token)
        ->and($controller->tokenValid)->toBeTrue();
});

test('index', function () {
    $controller = new SquizController();

    // Change squiz directory to a different one to confirm the directory is automatically created
    $controller->logPath = storage_path() . '/squiz-alt';

    $result = $controller->index();

    $viewPath = base_path('vendor/chisnall/squiz/src/Views/squiz.blade.php');

    expect(file_exists($controller->logPath))->toBeTrue()
        ->and($result)->toBeInstanceOf(Illuminate\View\View::class)
        ->and($result->getPath())->toBe($viewPath);
});

test('files', function () {
    $files = $this->squizController->files();

    $paths = collect($files)->map->getPathname()->all();

    expect($files)->toBeArray()
        ->and(count($files))->toBe(3);

    foreach ($this->tempSquizFiles as $id) {
        expect($paths)->toContain($this->tempSquizDirectory . '/' . $id . '.log');
    }
});

test('logIds', function () {
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

test('logEntries', function () {
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

test('getLogIds', function () {
    $result = $this->squizController->getLogIds();

    expect($result)->toBeInstanceOf(\Illuminate\Http\JsonResponse::class)
        ->and(count($result->getData()))->toBe(3);

    foreach ($this->tempSquizFiles as $id) {
        expect($result->getData())->toContain($id);
    }
});

test('getLogEntries', function () {
    $request = Request::create(uri: config('squiz.route_path'), parameters: ['logIds' => $this->tempSquizFiles]);

    $result = $this->squizController->getLogEntries($request);

    //$resultIds = array_map(fn($item) => $item->id, $result->getData());
    $resultIds = array_column($result->getData(), 'id');

    expect($result)->toBeInstanceOf(\Illuminate\Http\JsonResponse::class)
        ->and(count($result->getData()))->toBe(3);

    foreach ($this->tempSquizFiles as $id) {
        expect($resultIds)->toContain($id);
    }
});

test('clearLog', function () {
    $result = $this->squizController->clearLog();

    $files = $this->squizController->files();

    expect($result)->toBeInstanceOf(\Illuminate\Http\JsonResponse::class)
        ->and(count($files))->toBe(0)
        ->and($result->getData()->message)->toBe('Log cleared');
});

test('deleteEntry', function () {
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
