<?php

declare(strict_types=1);

namespace Qubus\Http\Emitter\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Qubus\Http\Emitter\Emitter;
use Qubus\Http\Emitter\SapiEmitter;

readonly class EmitterMiddleware implements MiddlewareInterface
{
    public function __construct(private Emitter $emitter = new SapiEmitter())
    {
    }

    /**
     * @inheritDoc
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);
        $this->emitter->emit($response);
        return $response;
    }
}
