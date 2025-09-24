<?php

declare(strict_types=1);

namespace Qubus\Http\Encryption\Env;

use function str_ends_with;
use function str_starts_with;

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

        $object = [];

        foreach ($lines as $line) {
            if (preg_match('/^\s*([\w\.\-]+)\s*=\s*(.*)?\s*$/', $line, $matches)) {
                $key = $matches[1];
                $value = $matches[2] ?? '';

                $length = $value ? strlen(string: $value) : 0;
                if ($length > 0 && str_starts_with($value, '"') && str_ends_with($value, '"')) {
                    $value = preg_replace(pattern: '/\\n/gm', replacement: "\n", subject: $value);
                }

                $value = trim(string: preg_replace(pattern: '/(^[\'"]|[\'"]$)/', replacement: '', subject: $value));

                $object[$key] = $value;
            }
        }

        return $object;
    }
}
