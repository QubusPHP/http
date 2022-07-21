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
use Qubus\Exception\Data\TypeException;
use Qubus\Http\Cookies\CookieCollection;
use Qubus\Http\Cookies\Cookies;

use function is_callable;
use function sprintf;

final class CookiesRequest
{
    public static function get(RequestInterface $request, string $name, ?string $value = null): CookieCollection
    {
        $cookies = Cookies::fromRequest($request);
        $cookie  = $cookies->get($name);

        if ($cookie) {
            return $cookie;
        }

        return CookieCollection::create($name, $value);
    }

    public static function set(RequestInterface $request, CookieCollection $cookie): RequestInterface
    {
        return Cookies::fromRequest($request)
            ->with($cookie)
            ->renderIntoCookieHeader($request);
    }

    public static function modify(RequestInterface $request, string $name, callable $modify): RequestInterface
    {
        if (! is_callable($modify)) {
            throw new TypeException(
                sprintf("'%s' must be callable.", $modify)
            );
        }

        $cookies = Cookies::fromRequest($request);
        $cookie  = $modify($cookies->has($name)
            ? $cookies->get($name)
            : CookieCollection::create($name));

        return $cookies
            ->with($cookie)
            ->renderIntoCookieHeader($request);
    }

    public static function remove(RequestInterface $request, string $name): RequestInterface
    {
        return Cookies::fromRequest($request)
            ->without($name)
            ->renderIntoCookieHeader($request);
    }
}
