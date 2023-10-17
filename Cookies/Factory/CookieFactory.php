<?php

/**
 * Qubus\Http
 *
 * @link       https://github.com/QubusPHP/http
 * @copyright  2022
 * @author     Joshua Parker <joshua@joshuaparker.dev>
 * @license    https://opensource.org/licenses/mit-license.php MIT License
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

class CookieFactory implements HttpCookieFactory
{
    public function __construct(protected ConfigContainer $config)
    {
    }

    /**
     * @inheritDoc
     */
    public function make(string $name, ?string $value = null, ?int $maxAge = null): SetCookieCollection
    {
        $cookie = SetCookieCollection::create(name: $name, value: $value);

        // Make sure we send both the MaxAge and Expires parameters (the former
        // is not supported by all browser versions)
        if ($maxAge) {
            $cookie = $cookie
                ->withMaxAge(maxAge: $maxAge)
                ->withExpires(expires: time() + $maxAge);
        }

        if (! is_null__(var: $this->domain())) {
            $cookie = $cookie->withDomain(domain: $this->domain());
        }

        // Explicitly set SameSite value, use sensible default if no value provided.
        $cookie = $cookie->withSameSite(sameSite: SameSite::{$this->samesite()}());

        return $cookie
            ->withPath(path: $this->path())
            ->withSecure(secure: $this->secure())
            ->withHttpOnly(httpOnly: true);
    }

    /**
     * @inheritDoc
     */
    public function expire(string $name): SetCookieCollection|string
    {
        try {
            return $this->make(name: $name)->expire();
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
        return $this->config->getConfigKey(key: 'cookies.path', default: '/');
    }

    /**
     * The cookie domain.
     *
     * @throws Exception
     */
    public function domain(): string
    {
        return $this->config->getConfigKey(key: 'cookies.domain', default: '');
    }

    /**
     * @throws Exception
     */
    public function secure(): bool
    {
        return $this->config->getConfigKey(key: 'cookies.secure', default: false);
    }

    /**
     * Cookie samesite. Default: 'lax'.
     *
     * @throws Exception
     */
    public function samesite(): string
    {
        return $this->config->getConfigKey(key: 'cookies.samesite', default: 'lax');
    }

    public function config(): ConfigContainer
    {
        return $this->config;
    }
}
