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

use function array_map;
use function urlencode;

final class CookieCollection
{
    private ?string $name = null;

    private ?string $value = null;

    public function __construct(string $name, ?string $value = null)
    {
        $this->name  = $name;
        $this->value = $value;
    }

    /**
     * Get cookie name.
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * Get cookie value.
     */
    public function getValue(): ?string
    {
        return $this->value;
    }

    /**
     * Sets cookie value
     */
    public function withValue(?string $value = null): self
    {
        $clone = clone $this;

        $clone->value = $value;

        return $clone;
    }

    /**
     * Render Cookie as a string.
     */
    public function __toString(): string
    {
        return urlencode($this->name) . '=' . urlencode((string) $this->value);
    }

    /**
     * Create a cookie.
     *
     * @param string      $name Cookie name.
     * @param string|null $value Cookie value.
     */
    public static function create(string $name, ?string $value = null): self
    {
        return new self($name, $value);
    }

    /**
     * Create a list of Cookies from a Cookie header value string.
     *
     * @return self[]
     */
    public static function listFromCookieString(string $string): array
    {
        $cookies = Util::splitOnAttributeDelimiter($string);

        return array_map(function ($cookiePair) {
            return self::oneFromCookiePair($cookiePair);
        }, $cookies);
    }

    /**
     * Create one Cookie from a cookie key/value header value string.
     */
    public static function oneFromCookiePair(string $string): self
    {
        [$cookieName, $cookieValue] = Util::splitCookiePair($string);

        /** @var CookieCollection $cookie */
        $cookie = new self($cookieName);

        if ($cookieValue !== null) {
            $cookie = $cookie->withValue($cookieValue);
        }

        return $cookie;
    }
}
