<?php

declare(strict_types=1);

namespace Qubus\Tests\Http\Core;

use PHPUnit\Framework\TestCase;
use Qubus\Http\Factories\Psr17Factory;

final class Psr17FactoryTest extends TestCase
{
    public function testCreatesAStreamFromContent(): void
    {
        $stream = new Psr17Factory()->createStream('hello');

        self::assertSame('hello', (string) $stream);
        self::assertSame(5, $stream->getSize());
    }

    public function testCreatesAResponseWithTheStandardReasonPhrase(): void
    {
        $response = new Psr17Factory()->createResponse(201);

        self::assertSame(201, $response->getStatusCode());
        self::assertSame('Created', $response->getReasonPhrase());
    }

    public function testCreatesAResponseWithACustomReasonPhrase(): void
    {
        $response = new Psr17Factory()->createResponse(299, 'Custom');

        self::assertSame('Custom', $response->getReasonPhrase());
    }

    public function testCreatesAServerRequest(): void
    {
        $request = new Psr17Factory()->createServerRequest('POST', 'https://example.com/items');

        self::assertSame('POST', $request->getMethod());
        self::assertSame('https://example.com/items', (string) $request->getUri());
        self::assertSame([], $request->getServerParams());
    }
}
