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

interface Session
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
     * @return mixed
     */
    public function get(string $name);

    /**
     * Sets the session.
     *
     * @param string $name Session name.
     * @param mixed  $value Value of the session set.
     */
    public function set(string $name, $value): void;

    /**
     * Returns all session data.
     *
     * @return array
     */
    public function getAll(): array;
}
