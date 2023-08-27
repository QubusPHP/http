<?php

/**
 * Qubus\Http
 *
 * @link       https://github.com/QubusPHP/http
 * @copyright  2020
 * @author     Joshua Parker <joshua@joshuaparker.dev>
 * @license    https://opensource.org/licenses/mit-license.php MIT License
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
