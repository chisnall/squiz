<?php

use Illuminate\Support\Facades\App;
use Symfony\Component\VarDumper\Cloner\VarCloner;
use Symfony\Component\VarDumper\Dumper\HtmlDumper;

// Recursively delete a directory
if (! function_exists('deleteDirectory')) {
    /**
     * @param  string  $path
     * @return bool
     *
     * @throws Exception
     */
    function deleteDirectory(string $path): bool
    {
        // Confirm path exists
        if (! file_exists($path)) {
            throw new Exception('Directory does not exist');
        }

        // Confirm path is a directory
        if (! is_dir($path)) {
            throw new Exception('Path is not a directory');
        }

        // Get files and directories
        $items = array_diff(scandir($path), ['.', '..']);

        // Attempt to delete each item
        try {
            foreach ($items as $item) {
                $fullPath = "$path/$item";

                if (is_dir($fullPath)) {
                    deleteDirectory($fullPath);
                } else {
                    // ErrorException on error
                    unlink($fullPath);
                }
            }

            // ErrorException on error
            rmdir($path);
        } catch (Throwable $exception) {
            throw new Exception('Permission denied');
        }

        return true;
    }
}

if (! function_exists('squiz')) {
    /**
     * @param  mixed  ...$entries
     * @return bool
     *
     * @throws Exception
     */
    function squiz(mixed ...$entries): bool
    {
        // Check for production environment
        if (App::environment('production')) {
            return false;
        }

        // Set storage path
        $storagePath = config('squiz.storage_path');

        // Check if running from Pest
        if (runningInPest()) {
            // Get storage path from application for certain Pest tests
            $pestTestName = test()->name();

            if (str_contains($pestTestName, 'where_storage_path_is_not_valid') ||
                str_contains($pestTestName, 'fails_to_creates_the_squiz_directory')
            ) {
                $storagePath = storage_path();
            }
        }

        // Confirm storage path exists
        if (! file_exists($storagePath)) {
            throw new Exception('Storage path does not exist');
        }

        // Check if squiz subdirectory exists
        if (! is_dir("$storagePath/squiz")) {
            try {
                mkdir("$storagePath/squiz");
            } catch (Exception $exception) {
                throw new Exception('Failed to create squiz subdirectory');
            }
        }

        // Init comment
        $comment = null;

        // Init terminated
        $terminated = false;

        // Process entries
        foreach ($entries as $index => $entry) {
            // Look for comment
            if (is_string($entry) && str_starts_with(strtolower($entry), 'comment:')) {
                $comment = preg_replace('/^comment:/i', '', $entry);
                unset($entries[$index]);

                continue;
            }

            // Look for called from squizd()
            if ($entry == '__TERMINATED__') {
                $terminated = true;
                unset($entries[$index]);
            }
        }

        // Determine output file
        $id = (int) (microtime(true) * 1_000_000);
        $outputFile = "$storagePath/squiz/$id.log";

        // Get backtrace
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);

        // Check if this was called from squizd() function
        if ($terminated) {
            // Determine file and line calling this
            $file = $backtrace[1]['file'] ?? 'unknown';
            $line = $backtrace[1]['line'] ?? 'unknown';
        } else {
            // Determine file and line calling this
            $file = $backtrace[0]['file'] ?? 'unknown';
            $line = $backtrace[0]['line'] ?? 'unknown';
        }

        // Check for view file
        if (str_starts_with($file, "$storagePath/framework/views/")) {
            // Read last line of the file
            $filePointer = fopen($file, 'r');
            $filePosition = -1;
            $lastLine = '';
            fseek($filePointer, $filePosition, SEEK_END);
            while (ftell($filePointer) > 0) {
                $character = fgetc($filePointer);
                if ($character === "\n" && $lastLine !== '') {
                    break;
                }
                $lastLine = $character.$lastLine;
                $filePosition--;
                fseek($filePointer, $filePosition, SEEK_END);
            }
            fclose($filePointer);

            // Extract the comment which provides the path to the view
            if (preg_match('#/\*\*PATH\s+(.*?)\s+ENDPATH\*\*/#', $lastLine, $matches)) {
                $path = $matches[1];

                if (file_exists($path)) {
                    $file = $path;
                }
            }
        }

        // Init HTML dump
        $htmlDump = null;

        // Process entries
        foreach ($entries as $index => $entry) {
            // Add spacer for 2nd and subsequent entries
            if ($index > 0) {
                $htmlDump .= '<div class="spacer"></div>';
            }

            // Check for backtrace alias
            if (is_string($entry) && strtolower($entry) == '__backtrace__' && $terminated) {
                $entry = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1];
            } elseif (is_string($entry) && strtolower($entry) == '__backtrace__') {
                $entry = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1)[0];
            }

            // Convert entry to Symfony VarDumper HTML
            $cloner = new VarCloner;
            $dumper = new HtmlDumper;
            $cloned = $cloner->cloneVar($entry);
            ob_start();
            $dumper->dump($cloned);
            $htmlDump .= ob_get_clean();
        }

        // Set output
        $output = [
            'id' => $id,
            'datetime' => now()->format('Y-m-d H:i:s'),
            'file' => $file,
            'line' => $line,
            'comment' => $comment,
            'terminated' => $terminated,
            'entry' => base64_encode($htmlDump),
        ];

        // Output file
        file_put_contents($outputFile, serialize($output));

        return true;
    }
}

if (! function_exists('squizd')) {
    /**
     * @param  mixed  ...$entries
     * @return bool
     *
     * @throws Exception
     */
    function squizd(mixed ...$entries): bool
    {
        // Check for production environment
        if (App::environment('production')) {
            return false;
        }

        // Pass the reserved __TERMINATED__ flag to squiz() to indicate termination
        $result = squiz('__TERMINATED__', ...$entries);

        // @codeCoverageIgnoreStart
        // Check if running from Pest or @squizd blade directive test
        if (! runningInPest() && $entries[0] != '__PEST_TEST__') {
            exit;
        }
        // @codeCoverageIgnoreEnd

        return $result;
    }
}

if (! function_exists('runningInPest')) {
    /**
     * @param  array<int, array{class: string}>|null  $backtrace
     * @return bool
     */
    function runningInPest(?array $backtrace = null): bool
    {
        // Get backtrace
        $backtrace ??= debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);

        // Check for class or subclass of \Tests\TestCase in the backtrace
        foreach ($backtrace as $frame) {
            if (isset($frame['class'])) {
                $class = $frame['class'];

                return $class === \Tests\TestCase::class || is_subclass_of($class, \Tests\TestCase::class);
            }
        }

        return false;
    }
}
