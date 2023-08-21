<?php

/**
 * Qubus\Http
 *
 * @link       https://github.com/QubusPHP/http
 * @copyright  2022 Joshua Parker <josh@joshuaparker.blog>
 * @copyright  2016 Thomas Nordahl Pedersen <thno@jfmedier.dk>
 * @copyright  2016 Rasmus Schultz (aka mindplay-dk) <rasc@jfmedier.dk>
 * @copyright  2016 Bo Andersen <boan@jfmedier.dk>
 * @license    https://opensource.org/licenses/mit-license.php MIT License
 *
 * @since      2.0.0
 */

declare(strict_types=1);

namespace Qubus\Http\Session\Storage;

/**
 * The Session Storage abstraction defines a
 * contract for reading/writing/deleting raw Session Data.
 */
interface SessionStorage
{
    /**
     * Read raw Session Data from underlying storage.
     *
     * @param string $sessionId
     * @return array|null
     */
    public function read(string $sessionId): ?array;

    /**
     * Write raw Session Data to underlying storage.
     *
     * @param string $sessionId
     * @param array $data
     * @param int $ttl time to live (in seconds)
     */
    public function write(string $sessionId, array $data, int $ttl): void;

    /**
     * Destroy the entire session by forcibly removing raw Session Data from underlying storage.
     *
     * Note that this differs substantially from {@see Session::clear()}, which is the appropriate
     * way to clear the current user's session - the `destroy()` method is used internally to
     * flush empty sessions from storage, but may also be useful for (rare) use-cases, such as
     * forcibly destroying the active session of a blocked/banned user.
     *
     * For actions such as users pressing a logout button, {@see Session::clear()} is more appropriate.
     */
    public function destroy(string $sessionId): void;
}
