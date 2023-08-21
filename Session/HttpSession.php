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

namespace Qubus\Http\Session;

use Qubus\Exception\Data\TypeException;

interface HttpSession
{
    public const COOKIE_NAME = "HTTPSESSID";

    /**
     * Retrieve session entity.
     *
     * @param string $type Fully qualified class name.
     * @throws TypeException Class name given does not exist.
     */
    public function get(string $type): SessionEntity;

    /**
     * Returns an array of object data.
     *
     * @return array
     */
    public function getData(): array;

    /**
     * Clear all session data, evict all objects from this Session, and renew {@see renew()}
     * the Session ID.
     *
     * Note that references to any session entity objects obtained via `get()` during the
     * same request will be *orphaned* from this Session - they will *not* be committed
     * to session state at the end of the request.
     *
     * (This is not as bad as it may sound, as very likely the only practical use-case for
     * `clear()` is a logout controller/action, during which likely no other session models
     * would be used or manipulated.)
     */
    public function clear(): void;

    /**
     * Explicitly renew the Session ID while *preserving* any Session data.
     *
     * The likely use-case is a login controller/action, where issuing a new Session ID,
     * while invalidating the previous Session ID, can provide an extra measure of security,
     * e.g. by avoiding very long-lived valid Session IDs.
     *
     * Note that periodic renewal of the Session ID is *not* recommended - issuing a new
     * Session ID should be done only after authentication, e.g. after successful validation
     * of user-supplied login credentials over a secure connection.
     *
     * @return void
     */
    public function renew(): void;

    /**
     * Returns the session's uuid which is derived
     * from the client session's id.
     */
    public function sessionId(): string;

    /**
     * Client session id which is stored in client's
     * cookie and never on the server.
     */
    public function clientSessionId(): string;
}
