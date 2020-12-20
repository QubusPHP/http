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

namespace Qubus\Http\Cookies;

use Psr\Http\Message\RequestInterface;
use Qubus\Http\Cookies\CookieCollection;

use function array_values;
use function implode;

class Cookies
{
    /**
     * The name of the Cookie header.
     */
    public const COOKIE_HEADER = 'Cookie';

    /** @var CookieCollection[] */
    private array $cookies = [];

    /**
     * @param array|CookieCollection[] $cookies
     */
    public function __construct(array $cookies = [])
    {
        foreach ($cookies as $cookie) {
            $this->cookies[$cookie->getName()] = $cookie;
        }
    }

    /**
     * Checks if cookie exists.
     *
     * @param string $name Cookie name.
     */
    public function has(string $name): bool
    {
        return isset($this->cookies[$name]);
    }

    /**
     * Retrieve cookie from the collection.
     */
    public function get(string $name): ?CookieCollection
    {
        if (! $this->has($name)) {
            return null;
        }

        return $this->cookies[$name];
    }

    /**
     * Returns a CookieCollection.
     *
     * @return array
     */
    public function getAll(): array
    {
        return array_values($this->cookies);
    }

    /**
     * Undocumented function
     */
    public function with(CookieCollection $cookie): Cookies
    {
        $clone = clone $this;

        $clone->cookies[$cookie->getName()] = $cookie;

        return $clone;
    }

    /**
     * Undocumented function
     */
    public function without(string $name): Cookies
    {
        $clone = clone $this;

        if (! $clone->has($name)) {
            return $clone;
        }

        unset($clone->cookies[$name]);

        return $clone;
    }

    /**
     * Render Cookies into a Request.
     */
    public function renderIntoCookieHeader(RequestInterface $request): RequestInterface
    {
        $cookieString = implode('; ', $this->cookies);

        $request = $request->withHeader(static::COOKIE_HEADER, $cookieString);

        return $request;
    }

    /**
     * Create Cookies from a Cookie header value string.
     */
    public static function fromCookieString(string $string): self
    {
        return new static(CookieCollection::listFromCookieString($string));
    }

    /**
     * Retrieves the cookie string.
     */
    public static function fromRequest(RequestInterface $request): Cookies
    {
        $cookieString = $request->getHeaderLine(static::COOKIE_HEADER);

        return static::fromCookieString($cookieString);
    }
}
