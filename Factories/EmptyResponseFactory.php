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

use Laminas\Diactoros\Response\EmptyResponse;
use Psr\Http\Message\ResponseInterface;

class EmptyResponseFactory
{
    /**
     * Create an empty response with the given status code.
     *
     * @param int $status Status code for the response, if any.
     * @param array $headers Headers for the response, if any.
     */
    public static function create(int $status = 204, array $headers = []): ResponseInterface
    {
        return new EmptyResponse($status, $headers);
    }
}
