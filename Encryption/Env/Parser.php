<?php

declare(strict_types=1);

namespace Qubus\Http\Encryption\Env;

use function explode;
use function str_contains;
use function str_starts_with;
use function trim;

final class Parser
{
    /**
     * Parses environment file variables and returns an associative array
     *
     * @param string $content
     * @return array
     */
    public static function parse(string $content): array
    {
        $lines = explode(separator: "\n", string: $content);

        $env = [];

        foreach ($lines as $line) {
            $line = trim(string: $line);

            // Skip empty or commented lines
            if ($line === '' || str_starts_with(haystack: $line, needle: '#')) {
                continue;
            }

            // Parse key=value pairs
            if (str_contains(haystack: $line, needle: '=')) {
                [$key, $value] = explode(separator: '=', string: $line, limit: 2);

                $key   = trim(string: $key);
                $value = trim(string: $value, characters: " \t\n\r\0\x0B'\""); // trim spaces and quotes

                $env[$key] = $value;
            }
        }

        return $env;
    }
}
