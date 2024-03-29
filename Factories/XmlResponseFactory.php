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
use Laminas\Diactoros\Response\XmlResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

final class XmlResponseFactory
{
    /**
     * Create an XML response.
     *
     * Produces an XML response with a Content-Type of application/xml and a default
     * status of 200.
     *
     * @param string|StreamInterface $xml String or stream for the message body.
     * @param int $status Integer status code for the response; 200 by default.
     * @param array $headers Array of headers to use at initialization.
     * @throws Exception|InvalidArgumentException If $text is neither a string or stream.
     */
    public static function create(
        string|StreamInterface $xml,
        int $status = 200,
        array $headers = []
    ): ResponseInterface {
        return new XmlResponse($xml, $status, $headers);
    }
}
