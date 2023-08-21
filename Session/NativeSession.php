<?php

/**
 * Qubus\Http
 *
 * @link       https://github.com/QubusPHP/http
 * @copyright  2020 Joshua Parker <josh@joshuaparker.blog>
 * @license    https://opensource.org/licenses/mit-license.php MIT License
 *
 * @since      1.0.0
 */

declare(strict_types=1);

namespace Qubus\Http\Session;

use Qubus\Config\ConfigContainer;
use Qubus\Exception\Exception;
use SessionHandler;
use SessionHandlerInterface;
use Throwable;

use function array_merge;
use function session_destroy;
use function session_get_cookie_params;
use function session_id;
use function session_name;
use function session_regenerate_id;
use function session_set_save_handler;
use function session_start;
use function session_status;

use const PHP_SESSION_ACTIVE;

class NativeSession implements PhpSession
{
    protected bool $started = false;

    protected const SESSION_OPTIONS = [
        'use_cookies'            => 1,
        'cookie_secure'          => 1,
        'use_only_cookies'       => 1,
        'cookie_httponly'        => 1,
        'use_strict_mode'        => 1,
        'sid_bits_per_character' => 5,
        'sid_length'             => 48,
        'cache_limiter'          => 'nocache',
        'cookie_samesite'        => 'Lax',
    ];

    public function __construct(
        protected ConfigContainer $config,
        ?SessionHandlerInterface $handler = null,
        protected ?string $sessionId = null
    ) {
        if ($handler === null) {
            $handler = new SessionHandler();
        }

        $this->sessionId($sessionId);

        session_set_save_handler($handler, true);
    }

    /**
     * Checks if session exists.
     *
     * @param string $name Session name.
     * @throws SessionException
     */
    public function has(string $name): bool
    {
        $this->startSession();

        return isset($_SESSION[$name]);
    }

    /**
     * Retrieve session.
     *
     * @param string $name Session name.
     * @return string|array
     * @throws SessionException
     */
    public function get(string $name): string|array
    {
        $this->startSession();

        return $_SESSION[$name];
    }

    /**
     * Sets the session.
     *
     * @param string $name Session name.
     * @param mixed $value Value of the session set.
     * @throws SessionException
     */
    public function set(string $name, $value): void
    {
        $this->startSession();

        $_SESSION[$name] = $value;
    }

    /**
     * Returns an array of session configOptions.
     *
     * @return array
     * @throws Exception
     */
    public function configOptions(): array
    {
        $config = [
            'use_cookies'            => $this->config->getConfigKey('session.use_cookies'),
            'cookie_secure'          => $this->config->getConfigKey('session.cookie_secure'),
            'use_only_cookies'       => $this->config->getConfigKey('session.use_only_cookies'),
            'cookie_httponly'        => $this->config->getConfigKey('session.cookie_httponly'),
            'use_strict_mode'        => $this->config->getConfigKey('session.use_strict_mode'),
            'sid_bits_per_character' => $this->config->getConfigKey('session.sid_bits_per_character'),
            'sid_length'             => $this->config->getConfigKey('session.sid_length'),
            'cache_limiter'          => $this->config->getConfigKey('session.cache_limiter'),
            'cookie_samesite'        => $this->config->getConfigKey('session.cookie_samesite'),
        ];

        return array_merge(self::SESSION_OPTIONS, $config);
    }

    /**
     * Returns true if sessions are enabled, and one exists.
     */
    public function isSessionActive(): bool
    {
        return session_status() === PHP_SESSION_ACTIVE;
    }

    /**
     * Returns the current session id if it exists. If not, it will be set.
     *
     * @param string|null $id Id of the session.
     * @return string|null
     */
    public function sessionId(?string $id = null): ?string
    {
        if (null !== $id) {
            $this->sessionId = $id;
        }

        return $this->sessionId;
    }

    /**
     * Updates the current session ID with a new one.
     * @throws SessionException
     */
    public function regenerateId(): void
    {
        if ($this->isSessionActive()) {
            try {
                if (session_regenerate_id(true)) {
                    $this->sessionId = session_id();
                }
            } catch (Throwable $e) {
                throw new SessionException('Failed to regenerate ID.', (int) $e->getCode(), $e);
            }
        }
    }

    /**
     * Starts a new session or resumes an existing session.
     * @throws SessionException
     */
    public function startSession(): void
    {
        if ($this->isSessionActive()) {
            return;
        }

        if ($this->sessionId !== null) {
            session_id($this->sessionId);
        }

        try {
            session_start($this->configOptions());
            $this->sessionId = session_id();
        } catch (Throwable $e) {
            throw new SessionException('Failed to start session.', (int) $e->getCode(), $e);
        }
    }

    /**
     * Returns the current session name.
     */
    public function currentSessionName(): string
    {
        return session_name();
    }

    /**
     * Destroys all session data.
     */
    public function destroySession(): void
    {
        if ($this->isSessionActive()) {
            session_destroy();
            $this->sessionId = null;
        }
    }

    /**
     * Destroy specific session data by key.
     * @throws SessionException
     */
    public function unsetSession(string $key): void
    {
        $this->startSession();
        unset($_SESSION[$key]);
    }

    /**
     * Returns all session data.
     *
     * @return array
     * @throws SessionException
     */
    public function getAll(): array
    {
        $this->startSession();
        return $_SESSION;
    }

    /**
     * @throws SessionException
     */
    public function clear(): void
    {
        $this->startSession();

        $_SESSION = [];
    }

    /**
     * Returns the session cookie parameters.
     *
     * @return array
     */
    public function getCookieParameters(): array
    {
        return session_get_cookie_params();
    }
}
