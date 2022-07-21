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

namespace Qubus\Http;

use Laminas\Diactoros\Response as BaseResponse;
use Psr\Http\Message\ResponseInterface;

final class Response extends BaseResponse implements ResponseInterface
{
    /**
     * @param string|resource|StreamInterface $body
     * @param array $headers
     */
    public function __construct($body = 'php://memory', int $status = 200, array $headers = [])
    {
        parent::__construct($body, $status, $headers);
    }
}
