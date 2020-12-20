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

namespace Qubus\Tests\Http\Cookies;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;
use Qubus\Tests\Http\Cookies\CookieMessageTesting;

class CookieRequestTesting implements RequestInterface
{
    use CookieMessageTesting;

    public function getRequestTarget(): void
    {
        throw new \RuntimeException('This method has not been implemented.');
    }

    /** {@inheritDoc} */
    public function withRequestTarget($requestTarget): void
    {
        throw new \RuntimeException('This method has not been implemented.');
    }

    public function getMethod(): void
    {
        throw new \RuntimeException('This method has not been implemented.');
    }

    /** {@inheritDoc} */
    public function withMethod($method): void
    {
        throw new \RuntimeException('This method has not been implemented.');
    }

    public function getUri(): void
    {
        throw new \RuntimeException('This method has not been implemented.');
    }

    /** {@inheritDoc} */
    public function withUri(UriInterface $uri, $preserveHost = false): void
    {
        throw new \RuntimeException('This method has not been implemented.');
    }
}
