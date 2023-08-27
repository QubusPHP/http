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

namespace Qubus\Http;

use Laminas\Diactoros\Response as BaseResponse;
use Psr\Http\Message\ResponseInterface;

final class Response extends BaseResponse implements ResponseInterface
{
    /**
     * @param string $body
     * @param int $status
     * @param array $headers
     */
    public function __construct($body = 'php://memory', int $status = 200, array $headers = [])
    {
        parent::__construct($body, $status, $headers);
    }
}
