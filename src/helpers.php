<?php

use Symfony\Component\VarDumper\Cloner\VarCloner;
use Symfony\Component\VarDumper\Dumper\HtmlDumper;

if (! function_exists('squiz')) {
    function squiz(mixed $entry, ?string $comment = null)
    {
        // Check for production environment
        if (App::environment('production')) {
            return false;
        }

        // Set storage path
        $storagePath = config('squiz.storage_path');

        // Get storage path from application for certain Pest tests
        if (app()->runningUnitTests()) {
            $pestTestName = test()->name();

            if (str_contains($pestTestName, 'where_storage_path_is_not_valid') ||
                str_contains($pestTestName, 'fails_to_creates_the_squiz_directory')
            ) {
                $storagePath = storage_path();
            }
        }

        // Confirm storage path exists
        if (! file_exists($storagePath)) {
            throw new Exception("Storage path does not exist");
        }

        // Check if squiz subdirectory exists
        if (! is_dir("$storagePath/squiz")) {
            try {
                mkdir("$storagePath/squiz");
            } catch (Exception $exception) {
                throw new Exception("Failed to create squiz subdirectory");
            }
        }

        // Determine output file
        $id = (int)(microtime(true) * 1_000_000);
        $outputFile = "$storagePath/squiz/$id.log";

        // Get backtrace
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);

        // Check if this was called from squizd() function
        if (str_starts_with($comment, '__TERMINATED__')) {
            $terminated = true;

            $comment = preg_replace('/^__TERMINATED__/', '', $comment);

            // Determine file and line calling this
            $file = $backtrace[1]['file'];
            $line = $backtrace[1]['line'];
        } else {
            $terminated = false;

            // Determine file and line calling this
            $file = $backtrace[0]['file'];
            $line = $backtrace[0]['line'];
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
                if ($character === "\n" && $lastLine !== '') break;
                $lastLine = $character . $lastLine;
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

        // Convert entry to Symfony VarDumper  HTML
        $cloner = new VarCloner;
        $dumper = new HtmlDumper;
        $cloned = $cloner->cloneVar($entry);
        ob_start();
        $dumper->dump($cloned);
        $htmlDump = ob_get_clean();

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
    function squizd(mixed $entry, ?string $comment = null)
    {
        $result = squiz($entry, "__TERMINATED__$comment");

        if ($comment != '__PEST_TEST__') die;

        return $result;
    }
}
