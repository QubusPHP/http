<?php

/**
 * Qubus\Http
 *
 * @link       https://github.com/QubusPHP/http
 * @copyright  2022 Joshua Parker
 * @copyright  2017 Flarum Framework
 * @license    https://opensource.org/licenses/mit-license.php MIT License
 *
 * @since      2.0.0
 */

declare(strict_types=1);

namespace Qubus\Http\Session\Middleware;

use Exception as GlobalException;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Qubus\Exception\Data\TypeException;
use Qubus\Exception\Exception;
use Qubus\Http\Cookies\CookiesResponse;
use Qubus\Http\Cookies\Factory\CookieFactory;
use Qubus\Http\Session\ClientSessionId;
use Qubus\Http\Session\HttpSession;
use Qubus\Http\Session\SessionData;
use Qubus\Http\Session\SessionId;
use Qubus\Http\Session\Storage\SessionStorage;
use Qubus\Http\Session\Validatable;

final class SessionMiddleware implements MiddlewareInterface
{
    private const SESSION_ATTRIBUTE = 'session';

    private array $options;

    public function __construct(
        private CookieFactory $cookie,
        private SessionStorage $storage,
    ) {
    }

    /**
     * Set the session options.
     * 
     * Example:
     *         [
     *            name => 'session name',
     *            attribute => 'session attribute', //usually 'session'
     *            lifetime => '300', //cookie lifetime in seconds
     *         ]
     * @param array $options
     * @return SessionMiddleware
     */
    public function withOptions(array $options): self
    {
        $this->options = $options;

        return $this;
    }

    /**
     * {@inheritDoc}
     * 
     * @throws Exception 
     * @throws TypeException 
     * @throws InvalidArgumentException 
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $session = $this->makeSession($request);

        $request = $request
            ->withAttribute($this->options['attribute'] ?? self::SESSION_ATTRIBUTE, $session);

        $response = $handler->handle($request);

        return $this->commitSession($response, $session);
    }

    /**
     * Create a Session for the given Request.
     * 
     * @param ServerRequestInterface $request 
     * @return HttpSession 
     * @throws GlobalException 
     * @throws TypeException 
     */
    private function makeSession(ServerRequestInterface $request): HttpSession
    {
        $cookies = $request->getCookieParams();

        if (isset($cookies[$this->options['name']])) {
            $clientSessionId = $cookies[$this->options['name']];

            $pattern = '/' . Validatable::VALID_PATTERN . '/';

            if (preg_match($pattern, $clientSessionId)) {
                $sessionId = SessionID::create($clientSessionId);

                $data = $this->storage->read($sessionId);

                if (is_array($data)) {
                    return SessionData::create($clientSessionId, $data, false);
                }
            }
        }
        return SessionData::create(ClientSessionId::create(), [], true);
    }

    /**
     * Commit Session to storage and add the Session Cookie to the given Response.
     * 
     * @param ResponseInterface $response 
     * @param HttpSession $session 
     * @return ResponseInterface 
     * @throws TypeException 
     * @throws Exception 
     * @throws InvalidArgumentException 
     */
    private function commitSession(ResponseInterface $response, HttpSession $session): ResponseInterface
    {
        $data = $session->getData();

        if ($session->isRenewed()) {
            // The session was renewed - destroy the data that was stored under the old Session ID:
            $this->storage->destroy($session->oldSessionId());
        }

        if (count($data) === 0) {
            // The session is empty - it should not be stored.
            if (! $session->isNew()) {
                // This session contained data previously and became empty - it should be destroyed:
                $this->storage->destroy($session->sessionId());
                // The cookie should be expired immediately:
                $response = CookiesResponse::set(
                    $response,
                    $this->cookie->make('', '', 0)
                );
            }
        } else {
            // The session contains data - it should be stored:
            $this->storage->write($session->sessionId(), $data, $this->getSessionLifetimeInSeconds());

            if ($session->isNew() || $session->isRenewed()) {
                // We've stored a new (or renewed) session - issue a cookie with the new Session ID:
                $response = CookiesResponse::set(
                    $response,
                    $this->cookie->make(
                        $this->options['name'] ?? $session::COOKIE_NAME,
                        $session->clientSessionId(), $this->getSessionLifetimeInSeconds()
                    )
                );
            }
        }

        return $response;
    }

    private function getSessionLifetimeInSeconds(): int
    {
        return $this->cookie->config()->getConfigKey('cookies.lifetime', $this->options['lifetime']);
    }
}