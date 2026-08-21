<?php

declare(strict_types=1);

namespace Qubus\Http\Swoole\Callback;

use Laminas\Diactoros\ServerRequest;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Swoole\Http\Request;
use Swoole\Http\Response;

use function extension_loaded;
use function Laminas\Diactoros\normalizeUploadedFiles;
use function str_contains;

use const PHP_SAPI;

final class RequestCallback
{
    private RequestHandlerInterface $handler;
    private RequestCallbackOptions $options;

    /**
     * @param callable(ServerRequestInterface):ResponseInterface $callable
     * @param RequestCallbackOptions|null $options
     * @return static
     */
    public static function fromCallable(callable $callable, ?RequestCallbackOptions $options = null): self
    {
        return new self(new CallableRequestHandler($callable), $options);
    }

    public function __construct(RequestHandlerInterface $handler, ?RequestCallbackOptions $options = null)
    {
        $this->handler = $handler;
        $this->options = $options ?? new RequestCallbackOptions();
    }

    public function __invoke(Request $request, Response $response): void
    {
        $this->emit($this->handler->handle($this->createServerRequest($request)), $response);
    }

    private function createServerRequest(Request $swooleRequest): ServerRequestInterface
    {
        /** @var array<string, string> $server */
        $server = $swooleRequest->server;

        /** @var array<array> | array<empty> $files */
        $files = $swooleRequest->files ?? [];

        /** @var array<string, string> | array<empty> $headers */
        $headers = $swooleRequest->header ?? [];

        /** @var array<string, string> | array<empty> $cookies */
        $cookies = $swooleRequest->cookie ?? [];

        /** @var array<string, string> | array<empty> $query_params */
        $query_params = $swooleRequest->get ?? [];

        /** @var array<string, mixed> | array<empty> $parsedBody */
        $parsedBody = $swooleRequest->post ?? [];

        $uri = $server['request_uri'] ?? '/';
        if (($server['query_string'] ?? '') !== '' && ! str_contains($uri, '?')) {
            $uri .= '?' . $server['query_string'];
        }

        return new ServerRequest(
            $server,
            normalizeUploadedFiles($files),
            $uri,
            $server['request_method'] ?? 'GET',
            $this->options->getStreamFactory()->createStream((string) $swooleRequest->rawContent()),
            $headers,
            $cookies,
            $query_params,
            $parsedBody,
        );
    }

    private function emit(ResponseInterface $psrResponse, Response $swooleResponse): void
    {
        if (! extension_loaded(extension: 'swoole') && ! extension_loaded(extension: 'openswoole')) {
            return;
        }

        if (PHP_SAPI !== 'cli') {
            return;
        }

        $swooleResponse->setStatusCode($psrResponse->getStatusCode(), $psrResponse->getReasonPhrase());

        foreach ($psrResponse->getHeaders() as $name => $values) {
            foreach ($values as $value) {
                $swooleResponse->setHeader($name, $value);
            }
        }

        $body = $psrResponse->getBody();
        $body->rewind();

        if ($body->isReadable()) {
            if ($body->getSize() <= $this->options->getResponseChunkSize()) {
                if ($contents = $body->getContents()) {
                    $swooleResponse->write($contents);
                }
            } else {
                while (!$body->eof() && ($contents = $body->read($this->options->getResponseChunkSize()))) {
                    $swooleResponse->write($contents);
                }
            }

            $swooleResponse->end();
        } else {
            $swooleResponse->end((string) $body);
        }

        $body->close();
    }
}
