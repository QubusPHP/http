<?php

/**
 * Qubus\Http
 *
 * @link       https://github.com/QubusPHP/http
 * @copyright  2022 Joshua Parker <josh@joshuaparker.blog>
 * @license    https://opensource.org/licenses/mit-license.php MIT License
 *
 * @since      2.0.0
 */

declare(strict_types=1);

namespace Qubus\Http\Cookies\Factory;

use Qubus\Config\ConfigContainer;
use Qubus\Exception\Data\TypeException;
use Qubus\Exception\Exception;
use Qubus\Http\Cookies\SameSite;
use Qubus\Http\Cookies\SetCookieCollection;

use function Qubus\Support\Helpers\is_null__;
use function time;

class CookieFactory
{
    public function __construct(protected ConfigContainer $config)
    {
    }

    /**
     * Make a new cookie instance.
     *
     * This method returns a cookie instance for use with the Set-Cookie HTTP header.
     * @throws TypeException
     * @throws Exception
     */
    public function make(string $name, ?string $value = null, ?int $maxAge = null): SetCookieCollection
    {
        $cookie = SetCookieCollection::create($name, $value);

        // Make sure we send both the MaxAge and Expires parameters (the former
        // is not supported by all browser versions)
        if ($maxAge) {
            $cookie = $cookie
                ->withMaxAge($maxAge)
                ->withExpires(time() + $maxAge);
        }

        if (! is_null__($this->domain())) {
            $cookie = $cookie->withDomain($this->domain());
        }

        // Explicitly set SameSite value, use sensible default if no value provided.
        $cookie = $cookie->withSameSite(SameSite::{$this->samesite()}());

        return $cookie
            ->withPath($this->path())
            ->withSecure($this->secure())
            ->withHttpOnly(true);
    }

    /**
     * Make an expired cookie instance.
     */
    public function expire(string $name): SetCookieCollection|string
    {
        try {
            return $this->make($name)->expire();
        } catch (TypeException | Exception $e) {
            return $e->getMessage();
        }
    }

    /**
     * The cookie path. Default: '/'.
     *
     * @throws Exception
     */
    public function path(): string|null
    {
        return $this->config->getConfigKey('cookie.path', '/');
    }

    /**
     * The cookie domain.
     *
     * @throws Exception
     */
    public function domain(): string
    {
        return $this->config->getConfigKey('cookie.domain', '');
    }

    /**
     * @throws Exception
     */
    public function secure(): bool
    {
        return $this->config->getConfigKey('cookie.secure', false);
    }

    /**
     * Cookie samesite. Default: 'lax'.
     *
     * @throws Exception
     */
    public function samesite(): string
    {
        return $this->config->getConfigKey('cookie.samesite', 'lax');
    }

    public function config(): ConfigContainer
    {
        return $this->config;
    }
}
