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

namespace Qubus\Http\Factories;

use Laminas\Diactoros\Response\HtmlResponse;

class HtmlResponseFactory
{
    /**
     * Create an HTML response.
     *
     * Produces an HTML response with a Content-Type of text/html and a default
     * status of 200.
     *
     * @param string|StreamInterface $html HTML or stream for the message body.
     * @param int $status Integer status code for the response; 200 by default.
     * @param array $headers Array of headers to use at initialization.
     * @throws Exception\InvalidArgumentException If $html is neither a string or stream.
     */
    public static function create($html, int $status = 200, array $headers = [])
    {
        return new HtmlResponse($html, $status, $headers);
    }
}
