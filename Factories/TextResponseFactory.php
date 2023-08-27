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

use Exception;
use InvalidArgumentException;
use Laminas\Diactoros\Response\TextResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

final class TextResponseFactory
{
    /**
     * Create a plain text response.
     *
     * Produces a text response with a Content-Type of text/plain and a default
     * status of 200.
     *
     * @param string|StreamInterface $text String or stream for the message body.
     * @param int $status Integer status code for the response; 200 by default.
     * @param array $headers Array of headers to use at initialization.
     * @throws Exception|InvalidArgumentException If $text is neither a string or stream.
     */
    public static function create(
        string|StreamInterface $text,
        int $status = 200,
        array $headers = []
    ): ResponseInterface {
        return new TextResponse($text, $status, $headers);
    }
}
