<?php

/**
 * Qubus\Http
 *
 * @link       https://github.com/QubusPHP/http
 * @copyright  2023
 * @author     Joshua Parker <joshua@joshuaparker.dev>
 * @license    https://opensource.org/licenses/mit-license.php MIT License
 */

declare(strict_types=1);

namespace Qubus\Http\Session;

use Exception;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Qubus\Exception\Data\TypeException;
use Qubus\Http\Cookies\CookiesResponse;
use Qubus\Http\Cookies\Factory\HttpCookieFactory;
use Qubus\Http\Session\Storage\SessionStorage;

class SessionService
{
    public const COOKIE_NAME = "QSESSID";

    public static array $options = [];

    public function __construct(
        public readonly SessionStorage $sessionStorage,
        public readonly HttpCookieFactory $cookie,
    ) {
    }

    /**
     * Create a Session for the given Request.
     *
     * @throws Exception
     */
    public function makeSession(ServerRequestInterface $request): HttpSession
    {
        $cookies = $request->getCookieParams();

        $cookieName = self::$options['cookie-name'] ?? self::COOKIE_NAME;

        if (isset($cookieName) && isset($cookies[$cookieName])) {
            $clientSessionId = $cookies[$cookieName];

            $pattern = '/' . Validatable::VALID_PATTERN . '/';

            if (preg_match(pattern: $pattern, subject: $clientSessionId)) {
                $sessionId = SessionId::create(id: $clientSessionId);

                $data = $this->sessionStorage->read(sessionId: $sessionId);

                if (is_array(value: $data)) {
                    return new SessionData(clientSessionId: $clientSessionId, data: $data, isNew: false);
                }
            }
        }
        return new SessionData(clientSessionId: ClientSessionId::create(), data: [], isNew: true);
    }

    /**
     * Commit Session to storage and add the Session Cookie to the given Response.
     *
     * @throws TypeException
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws Exception
     */
    public function commitSession(ResponseInterface $response, HttpSession $session): ResponseInterface
    {
        $data = $session->getData();

        if ($session->isRenewed()) {
            // The session was renewed - destroy the data that was stored under the old Session ID:
            $this->sessionStorage->destroy(sessionId: $session->oldSessionId());
        }

        if (count($data) === 0) {
            // The session is empty - it should not be stored.
            if (! $session->isNew()) {
                // This session contained data previously and became empty - it should be destroyed:
                $this->sessionStorage->destroy(sessionId: $session->sessionId());
                // The cookie should be expired immediately:
                $response = CookiesResponse::set(
                    response: $response,
                    setCookieCollection: $this->cookie->make(name: '', value: '', maxAge: 0)
                );
            }
        } else {
            // The session contains data - it should be stored:
            $this->sessionStorage->write($session->sessionId(), $data, $this->getSessionLifetimeInSeconds());

            if ($session->isNew() || $session->isRenewed()) {
                // We've stored a new (or renewed) session - issue a cookie with the new Session ID:
                $response = CookiesResponse::set(
                    response: $response,
                    setCookieCollection: $this->cookie->make(
                        name: self::$options['cookie-name'] ?? self::COOKIE_NAME,
                        value: $session->clientSessionId(),
                        maxAge: $this->getSessionLifetimeInSeconds()
                    )
                );
            }
        }

        return $response;
    }

    /**
     * @throws Exception
     */
    private function getSessionLifetimeInSeconds(): int
    {
        return self::$options['cookie-lifetime'] ?? $this->cookie->config()->getConfigKey(
            key: 'cookies.lifetime',
            default: 3600
        );
    }
}
