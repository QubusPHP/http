<?php

/**
 * Qubus\Http
 *
 * @link       https://github.com/QubusPHP/http
 * @copyright  2020 Joshua Parker <josh@joshuaparker.blog>
 * @copyright  2015 Beau Simensen <beau@dflydev.com>
 * @license    https://opensource.org/licenses/mit-license.php MIT License
 *
 * @since      1.0.0
 */

declare(strict_types=1);

namespace Qubus\Http\Cookies;

use Psr\Http\Message\ResponseInterface;
use Qubus\Exception\Data\TypeException;

use function is_callable;

final class CookiesResponse
{
    public static function get(ResponseInterface $response, string $name, ?string $value = null): SetCookieCollection
    {
        $setCookies = SetCookies::fromResponse($response);
        $cookie     = $setCookies->get($name);

        if ($cookie) {
            return $cookie;
        }

        return SetCookieCollection::create($name, $value);
    }

    /**
     * @param ResponseInterface $response
     * @param SetCookieCollection $setCookieCollection
     * @return ResponseInterface
     */
    public static function set(ResponseInterface $response, SetCookieCollection $setCookieCollection): ResponseInterface
    {
        return SetCookies::fromResponse($response)
            ->with($setCookieCollection)
            ->renderIntoSetCookieHeader($response);
    }

    /**
     * @throws TypeException
     */
    public static function expire(ResponseInterface $response, string $cookieName): ResponseInterface
    {
        return self::set($response, SetCookieCollection::createExpired($cookieName));
    }

    /**
     * @throws TypeException
     */
    public static function modify(ResponseInterface $response, string $name, callable $modify): ResponseInterface
    {
        if (! is_callable($modify)) {
            throw new TypeException('$modify must be callable.');
        }

        $setCookies = SetCookies::fromResponse($response);
        $setCookie  = $modify($setCookies->has($name)
            ? $setCookies->get($name)
            : SetCookieCollection::create($name));

        return $setCookies
            ->with($setCookie)
            ->renderIntoSetCookieHeader($response);
    }

    public static function remove(ResponseInterface $response, string $name): ResponseInterface
    {
        return SetCookies::fromResponse($response)
            ->without($name)
            ->renderIntoSetCookieHeader($response);
    }
}
