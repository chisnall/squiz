<?php

namespace Chisnall\Squiz\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class SquizController extends Controller
{
    private ?string $appToken;

    public function __construct()
    {
        // Get token from URL query parameter, otherwise from header
        // URL query parameter token is used for the view when visiting the page
        // Header token is used by JS requests
        $this->appToken = request()->get('token') ?? request()->header('X-SQUIZ-TOKEN');

        if (config('app.env') == 'local') {
            return;
        }

        if ($this->appToken != config('squiz.token')) {
            abort(404);
        }
    }

    public function index()
    {
        if (class_exists(\Barryvdh\Debugbar\Facades\Debugbar::class)) {
            \Barryvdh\Debugbar\Facades\Debugbar::disable();
        }

        // Check if subdirectory exists
        if (! file_exists(storage_path('squiz'))) {
            mkdir(storage_path('squiz'));
        }

        $logIds = $this->logIds();

        $logEntries = $this->logEntries();

        return view()->file(base_path('vendor/chisnall/squiz/src/Views/squiz.blade.php'), ['logIds' => $logIds, 'logEntries' => $logEntries]);
    }

    private function files(): array
    {
        $logDirectory = storage_path('squiz');

        $files = File::files($logDirectory);

        return $files;
    }

    private function logIds()
    {
        $logIds = [];

        $files = $this->files();

        foreach ($files as $file) {
            $contents = File::get($file);

            $entry = unserialize($contents);

            $logIds[] = $entry['id'];
        }

        return $logIds;
    }

    private function logEntries(): array
    {
        $entries = [];

        $files = $this->files();

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
        $entries = $this->logIds();

        return response()->json($entries);
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

        $file = storage_path('squiz') . "/$entryId.log";

        File::delete($file);

        return response()->json([
            'message' => 'Entry deleted: ' . $entryId
        ]);
    }
}
