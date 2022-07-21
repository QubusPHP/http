<?php

/**
 * Qubus\Http
 *
 * @link       https://github.com/QubusPHP/http
 * @copyright  2022 Joshua Parker
 * @license    https://opensource.org/licenses/mit-license.php MIT License
 *
 * @since      2.0.0
 */

declare(strict_types=1);

namespace Qubus\Http;

use Laminas\Diactoros\ServerRequest as BaseServerRequest;
use Psr\Http\Message\ServerRequestInterface;

final class ServerRequest extends BaseServerRequest implements ServerRequestInterface
{
    public function __construct(
        array $serverParams = [],
        array $uploadedFiles = [],
        $uri = null,
        ?string $method = null,
        $body = 'php://input',
        array $headers = [],
        array $cookies = [],
        array $queryParams = [],
        $parsedBody = null,
        string $protocol = '1.1'
    ) {
        parent::__construct(
            $serverParams,
            $uploadedFiles,
            $uri,
            $method,
            $body,
            $headers,
            $cookies,
            $queryParams,
            $parsedBody,
            $protocol
        );
    }
}
