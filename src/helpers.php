<?php

use Symfony\Component\VarDumper\Cloner\VarCloner;
use Symfony\Component\VarDumper\Dumper\HtmlDumper;

if (! function_exists('squiz')) {
    function squiz(mixed $entry, ?string $comment = null)
    {
        // Determine storage path
        // If running from PHPUnit, we need to use the env variable
        // defined in the phpunit.xml environment variables section as:
        // <env name="LARAVEL_STORAGE_PATH" value="[storage path here]"/>
        if (function_exists('app') && app() instanceof Illuminate\Foundation\Application) {
            // Check for production environment
            if (App::environment('production')) {
                return;
            }

            // Get storage path from application
            $storagePath = storage_path();
        } else {
            // Get storage path from phpunit.xml environment variable
            $storagePath = env('LARAVEL_STORAGE_PATH');
        }

        // Confirm we have a storage path
        if (! $storagePath) {
            return;
        }

        // Confirm storage path exists
        if (! file_exists($storagePath)) {
            return;
        }

        // Check if subdirectory exists
        if (! file_exists("$storagePath/squiz")) {
            mkdir("$storagePath/squiz");

            if (! file_exists("$storagePath/squiz")) {
                return;
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
    }
}

if (! function_exists('squizd')) {
    function squizd(mixed $entry, ?string $comment = null)
    {
        squiz($entry, "__TERMINATED__$comment");
        die;
    }
}
