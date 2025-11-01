<?php

namespace Chisnall\Squiz\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class SquizController extends Controller
{
    public ?string $appToken;
    public ?string $logPath;
    public ?bool $tokenValid;

    public function __construct()
    {
        // Get token from URL query parameter, otherwise from header
        // URL query parameter token is used for the view when visiting the page
        // Header token is used by JS requests
        $this->appToken = request()->get('token') ?? request()->header('X-SQUIZ-TOKEN');
        $this->logPath = config('squiz.storage_path') . '/squiz';

        if (App::environment() == 'local') {
            $this->tokenValid = null;
            return;
        }

        if ($this->appToken != config('squiz.token')) {
            $this->tokenValid = false;
            abort(404);
        }

        $this->tokenValid = true;
    }

    public function index()
    {
        if (class_exists(\Barryvdh\Debugbar\Facades\Debugbar::class)) {
            \Barryvdh\Debugbar\Facades\Debugbar::disable();
        }

        if (! file_exists($this->logPath)) {
            mkdir($this->logPath);
        }

        $logIds = $this->logIds();

        $logEntries = $this->logEntries();

        return view()->file(base_path('vendor/chisnall/squiz/src/Views/squiz.blade.php'), ['logIds' => $logIds, 'logEntries' => $logEntries]);
    }

    public function files(): array
    {
        return File::files($this->logPath);
    }

    public function logIds(?array $files = null)
    {
        $logIds = [];

        $files ??= $this->files();

        foreach ($files as $file) {
            try {
                $contents = File::get($file);

                $entry = unserialize($contents);

                $logIds[] = $entry['id'];
            } catch (\Throwable $exception) {
                //
            }
        }

        return $logIds;
    }

    public function logEntries(?array $files = null): array
    {
        $entries = [];

        $files ??= $this->files();

        foreach ($files as $file) {
            try {
                $contents = File::get($file);

                $entry = unserialize($contents);

                $entries[$entry['id']] = [
                    'id' => $entry['id'],
                    'datetime' => $entry['datetime'],
                    'file' => $entry['file'],
                    'line' => $entry['line'],
                    'comment' => $entry['comment'],
                    'entry' => $entry['entry'],
                    'terminated' => $entry['terminated'],
                ];
            } catch (\Throwable $exception) {
                //
            }
        }

        return $entries;
    }

    public function getLogIds(): JsonResponse
    {
        return response()->json($this->logIds());
    }

    public function getLogEntries(Request $request): JsonResponse
    {
        $logIds = $request['logIds'];

        $entries = $this->logEntries();

        $returnEntries = [];

        foreach ($logIds as $logId) {
            $returnEntries[] = $entries[$logId];
        }

        return response()->json($returnEntries);
    }

    public function clearLog(): JsonResponse
    {
        $files = $this->files();

        foreach ($files as $file) {
            File::delete($file);
        }

        return response()->json([
            'message' => 'Log cleared'
        ]);
    }

    public function deleteEntry(Request $request): JsonResponse
    {
        $entryId = $request['entryId'];

        File::delete("$this->logPath/$entryId.log");

        return response()->json([
            'message' => "Entry deleted: $entryId"
        ]);
    }
}
