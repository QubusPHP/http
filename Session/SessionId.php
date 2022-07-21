<?php

/**
 * Qubus\Http
 *
 * @link       https://github.com/QubusPHP/http
 * @copyright  2022 Joshua Parker
 * @license    https://opensource.org/licenses/mit-license.php MIT License
 *
 * @since      2.0.0
 */

declare(strict_types=1);

namespace Qubus\Http\Session;

class SessionId
{
    public static function create(?string $id = null): string
    {
        // Generate 36 bytes (288 bits) of random data or use the id passed into the function.
        $id = $id ?? random_bytes(36);

        assert(strlen($id) === 36);

        // Set version to 0100
        $id[6] = chr(ord($id[6]) & 0x0f | 0x40);
        // Set bits 6-7 to 10
        $id[8] = chr(ord($id[8]) & 0x3f | 0x80);

        // Output the 36 character UUID.
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(sha1($id), 4));
    }    
}
