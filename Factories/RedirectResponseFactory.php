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

namespace Qubus\Http\Factories;

use Laminas\Diactoros\Response\RedirectResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;

final class RedirectResponseFactory
{
    /**
     * Create a redirect response.
     *
     * Produces a redirect response with a Location header and the given status
     * (302 by default).
     *
     * Note: this method overwrites the `location` $headers value.
     *
     * @param string|UriInterface $uri URI for the Location header.
     * @param int $status Integer status code for the redirect; 302 by default.
     * @param array $headers Array of headers to use at initialization.
     */
    public static function create(
        string|UriInterface $uri,
        int $status = 302,
        array $headers = []
    ): ResponseInterface {
        return new RedirectResponse($uri, $status, $headers);
    }
}
