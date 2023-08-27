<?php

/**
 * Qubus\Http
 *
 * @link       https://github.com/QubusPHP/http
 * @copyright  2020
 * @author     Joshua Parker <joshua@joshuaparker.dev>
 * @license    https://opensource.org/licenses/mit-license.php MIT License
 */

declare(strict_types=1);

namespace Qubus\Http\Cookies;

use Psr\Http\Message\RequestInterface;

use function array_values;
use function implode;

final class Cookies
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

    public function with(CookieCollection $cookie): Cookies
    {
        $clone = clone $this;

        $clone->cookies[$cookie->getName()] = $cookie;

        return $clone;
    }

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

        return $request->withHeader(self::COOKIE_HEADER, $cookieString);
    }

    /**
     * Create Cookies from a Cookie header value string.
     */
    public static function fromCookieString(string $string): self
    {
        return new self(CookieCollection::listFromCookieString($string));
    }

    /**
     * Retrieves the cookie string.
     */
    public static function fromRequest(RequestInterface $request): Cookies
    {
        $cookieString = $request->getHeaderLine(self::COOKIE_HEADER);

        return self::fromCookieString($cookieString);
    }
}
