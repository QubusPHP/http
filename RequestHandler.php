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

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Server\MiddlewareInterface;

final class RequestHandler implements RequestHandlerInterface
{
    final public const RESPONSE_CODE = 200;

    public function __construct(
        /** @var ResponseFactoryInterface */
        private ResponseFactoryInterface $responseFactory,
        /** @var MiddlewareInterface[] */
        private array $middlewares = []
    ) {
    }

    public function handle(ServerRequestInterface $serverRequest): ResponseInterface
    {
        $middleware = $this->middlewares[0] ?? false;
        array_shift($this->middlewares);

        return 
            $middleware ? 
            $middleware->process(
                $serverRequest,
                new self($this->responseFactory, $this->middlewares)
            ) : $this->responseFactory->createResponse(self::RESPONSE_CODE);
    }
}
