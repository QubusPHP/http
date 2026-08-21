<?php

declare(strict_types=1);

namespace Qubus\Tests\Http\Core;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Qubus\Http\Factories\Psr17Factory;
use Qubus\Http\RequestHandler;

final class RequestHandlerTest extends TestCase
{
    public function testMiddlewarePipelineCanBeReused(): void
    {
        $calls = 0;
        $middleware = new class ($calls) implements MiddlewareInterface {
            public function __construct(private int &$calls)
            {
            }

            public function process(
                ServerRequestInterface $request,
                RequestHandlerInterface $handler
            ): ResponseInterface {
                $this->calls++;

                return $handler->handle($request);
            }
        };

        $factory = new Psr17Factory();
        $handler = new RequestHandler($factory, [$middleware]);
        $request = $factory->createServerRequest('GET', '/');

        $handler->handle($request);
        $handler->handle($request);

        self::assertSame(2, $calls);
    }
}
