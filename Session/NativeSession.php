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

use Qubus\Config\Collection;
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

class NativeSession implements Session
{
    protected ?string $sessionId = null;

    protected Collection $config;

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

    public function __construct(Collection $config, ?SessionHandlerInterface $handler = null)
    {
        if ($handler === null) {
            $handler = new SessionHandler();
        }

        session_set_save_handler($handler, true);

        $this->config = $config;
    }

    /**
     * Checks if session exists.
     *
     * @param string $name Session name.
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
     * @return string
     */
    public function get(string $name)
    {
        $this->startSession();

        return $_SESSION[$name];
    }

    /**
     * Sets the session.
     *
     * @param string $name Session name.
     * @param mixed  $value Value of the session set.
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
     */
    public function configOptions(): array
    {
        $config = [
            'use_cookies'            => (string) $this->config->getConfigKey('session.use_cookies'),
            'cookie_secure'          => (string) $this->config->getConfigKey('session.cookie_secure'),
            'use_only_cookies'       => (string) $this->config->getConfigKey('session.use_only_cookies'),
            'cookie_httponly'        => (string) $this->config->getConfigKey('session.cookie_httponly'),
            'use_strict_mode'        => (string) $this->config->getConfigKey('session.use_strict_mode'),
            'sid_bits_per_character' => (string) $this->config->getConfigKey('session.sid_bits_per_character'),
            'sid_length'             => (string) $this->config->getConfigKey('session.sid_length'),
            'cache_limiter'          => (string) $this->config->getConfigKey('session.cache_limiter'),
            'cookie_samesite'        => (string) $this->config->getConfigKey('session.cookie_samesite'),
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
     * @param string $sessionId Id of the session.
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
     * Returnes the current session name.
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
     */
    public function getAll(): array
    {
        $this->startSession();
        return $_SESSION;
    }

    public function clear(): void
    {
        $this->open();
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
