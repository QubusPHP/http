<?php

declare(strict_types=1);

namespace Qubus\Tests\Http\Core;

use Laminas\Diactoros\StreamFactory;
use PHPUnit\Framework\TestCase;
use Qubus\Http\Request;

final class RequestTest extends TestCase
{
    private array $server;
    private array $get;
    private array $post;
    private array $files;

    protected function setUp(): void
    {
        $this->server = $_SERVER;
        $this->get = $_GET;
        $this->post = $_POST;
        $this->files = $_FILES;
        $_SERVER = $_GET = $_POST = $_FILES = [];
    }

    protected function tearDown(): void
    {
        $_SERVER = $this->server;
        $_GET = $this->get;
        $_POST = $this->post;
        $_FILES = $this->files;
    }

    public function testExplicitUriAndMethodAreNotReplacedByGlobals(): void
    {
        $request = new Request('https://example.com/path?x=1', 'POST');

        self::assertSame('https://example.com/path?x=1', (string) $request->getUri());
        self::assertSame('https://example.com/path?x=1', (string) $request->getUrl());
        self::assertSame('POST', $request->getMethod());
        self::assertTrue($request->isPost());
        self::assertTrue($request->isPostBack());
        self::assertTrue($request->isSecure());
        self::assertSame('1', $request->handler()->value('x'));
    }

    public function testBuildsARequestFromServerGlobals(): void
    {
        $_SERVER = [
            'HTTP_HOST' => 'example.com',
            'REQUEST_URI' => '/from-globals?x=1',
            'REQUEST_METHOD' => 'GET',
            'HTTPS' => 'on',
        ];

        $request = new Request();

        self::assertSame('https://example.com/from-globals?x=1', (string) $request->getUri());
        self::assertTrue($request->hasServer('request-method'));
        self::assertSame([], $request->getAcceptFormats());
    }

    public function testParsesJsonRequestBodiesWithoutMovingTheStreamCursor(): void
    {
        $body = new StreamFactory()->createStream('{"name":"Ada"}');
        $request = new Request(
            uri: '/users',
            method: 'POST',
            body: $body,
            headers: ['Content-Type' => 'application/json; charset=utf-8']
        );

        self::assertSame('Ada', $request->handler()->value('name'));
        self::assertSame(0, $request->getBody()->tell());
        self::assertSame('application/json', $request->getContentType());
    }

    public function testMethodOverridesOnlyApplyToPostRequests(): void
    {
        $_POST[Request::FORCE_METHOD_KEY] = 'DELETE';

        self::assertSame('GET', new Request('/items', 'GET')->getMethod());
        self::assertSame('DELETE', new Request('/items', 'POST')->getMethod());
    }

    public function testForwardedProtocolMustBeExplicitlyTrusted(): void
    {
        $_SERVER['HTTP_X_FORWARDED_PROTO'] = 'https';
        $request = new Request('/path', 'GET');

        self::assertFalse($request->isSecure());
        self::assertTrue($request->isSecure(trustForwardedHeader: true));
    }
}
