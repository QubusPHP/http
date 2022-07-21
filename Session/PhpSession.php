<?php

/**
 * Qubus\Http
 *
 * @link       https://github.com/QubusPHP/http
 * @copyright  2020 Joshua Parker
 * @license    https://opensource.org/licenses/mit-license.php MIT License
 *
 * @since      1.0.0
 */

declare(strict_types=1);

namespace Qubus\Http\Session;

interface PhpSession
{
    /**
     * Checks if session exists.
     *
     * @param string $name Session name.
     */
    public function has(string $name): bool;

    /**
     * Retrieve session.
     *
     * @param string $name Session name.
     */
    public function get(string $name): string|array;

    /**
     * Returns all session data.
     *
     * @return array
     */
    public function getAll(): array;

    /**
     * Destroy specific session data by key.
     */
    public function unsetSession(string $key): void;
}
