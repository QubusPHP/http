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

use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;

final class JsonResponseFactory
{
    /**
     * Create a JSON response with the given data.
     *
     * @param mixed $data Data to convert to JSON.
     * @param int $status Integer status code for the response; 200 by default.
     * @param array $headers Array of headers to use at initialization.
     * @param int $encodingOptions JSON encoding options to use.
     * @throws Exception\InvalidArgumentException If unable to encode the $data to JSON.
     */
    public static function create(
        $data,
        int $status = 200,
        array $headers = [],
        int $encodingOptions = 79
    ): ResponseInterface {
        return new JsonResponse($data, $status, $headers, $encodingOptions);
    }
}
