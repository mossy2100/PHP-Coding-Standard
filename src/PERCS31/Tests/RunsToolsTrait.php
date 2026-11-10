<?php

declare(strict_types=1);

namespace PERCS31\Tests;

use RuntimeException;

/**
 * Runs phpcs or phpcbf from the package's vendor directory, as a user would, on fixtures or temporary files.
 */
trait RunsToolsTrait
{
    /**
     * Run phpcs or phpcbf, and return its exit code and output.
     *
     * @param string $tool The tool, 'phpcs' or 'phpcbf'.
     * @param list<string> $args The arguments, after the tool's name and `-q`.
     * @return array{exitCode: int, output: string} The exit code, and stdout followed by stderr.
     */
    private function runTool(string $tool, array $args): array
    {
        $command = [PHP_BINARY, dirname(__DIR__, 3) . "/vendor/bin/$tool", '-q', ...$args];
        $process = proc_open($command, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        if ($process === false) {
            throw new RuntimeException("Couldn't run $tool.");
        }

        $output = (string) stream_get_contents($pipes[1]) . (string) stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);

        return ['exitCode' => proc_close($process), 'output' => $output];
    }

    /**
     * Write a file's contents, or the given contents, to a new temporary file.
     *
     * @param ?string $source The file to copy, or null to use $contents.
     * @param string $contents The contents to write when $source is null.
     * @param string $extension The temporary file's extension.
     * @return string The temporary file's path.
     */
    private function copyToTempFile(?string $source, string $contents = '', string $extension = '.php'): string
    {
        $path = sys_get_temp_dir() . '/mossy2100-cs-' . bin2hex(random_bytes(8)) . $extension;
        file_put_contents($path, $source !== null ? file_get_contents($source) : $contents);

        return $path;
    }
}
