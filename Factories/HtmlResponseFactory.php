<?php

/**
 * Qubus\Http
 *
 * @link       https://github.com/QubusPHP/http
 * @copyright  2020 Joshua Parker <josh@joshuaparker.blog>
 * @license    https://opensource.org/licenses/mit-license.php MIT License
 *
 * @since      1.0.0
 */

declare(strict_types=1);

namespace Qubus\Http\Factories;

use Exception;
use InvalidArgumentException;
use Laminas\Diactoros\Response\HtmlResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

final class HtmlResponseFactory
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
     * @throws Exception|InvalidArgumentException If $html is neither a string or stream.
     */
    public static function create(
        string|StreamInterface $html,
        int $status = 200,
        array $headers = []
    ): ResponseInterface {
        return new HtmlResponse($html, $status, $headers);
    }
}
