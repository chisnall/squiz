<?php

namespace Chisnall\Squiz\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class SquizController extends Controller
{
    public ?string $logPath;

    /**
     * @param  string|null  $logPath
     */
    public function __construct(?string $logPath = null)
    {
        // Disable debugbar
        if (app()->bound('debugbar')) {
            app('debugbar')->disable();
        }

        // Set log path
        $this->logPath = $logPath ?? config('squiz.storage_path').'/squiz';

        // Create log path if it doesn't exist'
        if (! file_exists($this->logPath)) {
            mkdir($this->logPath);
        }
    }

    /**
     * @return View
     */
    public function index(): View
    {
        // Get log IDs and entries
        $logIds = $this->logIds();
        $logEntries = $this->logEntries();

        // Return view
        return view()->file(base_path('vendor/chisnall/squiz/src/Views/squiz.blade.php'), ['logIds' => $logIds, 'logEntries' => $logEntries]);
    }

    /**
     * @return list<\Symfony\Component\Finder\SplFileInfo>
     */
    public function files(): array
    {
        // Get log files
        return File::files($this->logPath);
    }

    /**
     * @param  list<\Symfony\Component\Finder\SplFileInfo>|null  $files
     * @return list<int>
     */
    public function logIds(?array $files = null): array
    {
        // Init log IDs
        $logIds = [];

        // Get log files
        $files ??= $this->files();

        // Process log files
        foreach ($files as $file) {
            try {
                // Read file contents
                $contents = File::get($file);

                // Unserialize file contents
                $entry = unserialize($contents);

                //  Add to log IDs
                $logIds[] = $entry['id'];
            } catch (\Throwable $exception) {
                //
            }
        }

        // Return log IDs
        return $logIds;
    }

    /**
     * @param  list<\Symfony\Component\Finder\SplFileInfo>|null  $files
     * @return array<int, array{id: int, datetime: string, file: string, line: int, comment: string|null, entry: string, terminated: bool}>
     */
    public function logEntries(?array $files = null): array
    {
        // Init entries
        $entries = [];

        // Get log files
        $files ??= $this->files();

        // Process log files
        foreach ($files as $file) {
            try {
                // Read file contents
                $contents = File::get($file);

                // Unserialize file contents
                $entry = unserialize($contents);

                // Add to entries
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

        // Return entries
        return $entries;
    }

    /**
     * @return JsonResponse
     */
    public function getLogIds(): JsonResponse
    {
        // Return response
        return response()->json($this->logIds());
    }

    /**
     * @param  Request  $request
     * @return JsonResponse
     */
    public function getLogEntries(Request $request): JsonResponse
    {
        // Get log IDs from request
        $logIds = $request['logIds'];

        // Get entries
        $entries = $this->logEntries();

        // Init return entries
        $returnEntries = [];

        // process log IDs
        foreach ($logIds as $logId) {
            // Add to return entries
            $returnEntries[] = $entries[$logId];
        }

        // Return response
        return response()->json($returnEntries);
    }

    /**
     * @return JsonResponse
     */
    public function clearLog(): JsonResponse
    {
        // Get log files
        $files = $this->files();

        // Delete log files
        foreach ($files as $file) {
            File::delete($file);
        }

        // Return response
        return response()->json([
            'message' => 'Log cleared',
        ]);
    }

    /**
     * @param  Request  $request
     * @return JsonResponse
     */
    public function deleteEntry(Request $request): JsonResponse
    {
        // Get entry ID from request
        $entryId = $request['entryId'];

        // Delete file
        File::delete("$this->logPath/$entryId.log");

        // Return response
        return response()->json([
            'message' => "Entry deleted: $entryId",
        ]);
    }
}
