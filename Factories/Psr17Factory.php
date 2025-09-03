<?php

declare(strict_types=1);

namespace Qubus\Http\Factories;

use Laminas\Diactoros\Stream;
use Laminas\Diactoros\UploadedFile;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileFactoryInterface;
use Psr\Http\Message\UploadedFileInterface;
use Psr\Http\Message\UriFactoryInterface;
use Psr\Http\Message\UriInterface;
use Qubus\Exception\Data\TypeException;
use Qubus\Http\Exception\MalformedUrlException;
use Qubus\Http\Response;
use Qubus\Http\ServerRequest;
use Qubus\Http\Url;
use RuntimeException;

use function error_get_last;
use function fopen;
use function in_array;
use function sprintf;

use const UPLOAD_ERR_OK;

class Psr17Factory extends RequestFactory implements
    UriFactoryInterface,
    UploadedFileFactoryInterface,
    StreamFactoryInterface,
    ServerRequestFactoryInterface,
    ResponseFactoryInterface
{
    public function createResponse(int $code = 200, string $reasonPhrase = ''): ResponseInterface
    {
        if (2 > \func_num_args()) {
            // This will make the Response class to use a custom reasonPhrase
            $reasonPhrase = null;
        }
        return new Response(body: null, status: $code, headers:[])
                ->withProtocolVersion(version: '1.1')
                ->withStatus(code: $code, reasonPhrase: $reasonPhrase);
    }

    public function createServerRequest(string $method, $uri, array $serverParams = []): ServerRequestInterface
    {
        return new ServerRequest(
            serverParams: $serverParams,
            uri: $uri,
            method: $method,
            body: null,
            headers: [],
            protocol: '1.1'
        );
    }

    public function createStream(string $content = ''): StreamInterface
    {
        return new Stream(stream: $content);
    }

    /**
     * @throws TypeException
     */
    public function createStreamFromFile(string $filename, string $mode = 'r'): StreamInterface
    {
        if ('' === $filename) {
            throw new RuntimeException(message: 'Path cannot be empty');
        }

        if (false === $resource = @fopen(filename: $filename, mode: $mode)) {
            if (
                '' === $mode || false === in_array(
                    needle: $mode[0],
                    haystack: ['r', 'w', 'a', 'x', 'c'],
                    strict: true
                )
            ) {
                throw new TypeException(message: sprintf('The mode "%s" is invalid.', $mode));
            }

            throw new RuntimeException(
                message: sprintf(
                    'The file "%s" cannot be opened: %s',
                    $filename,
                    error_get_last()['message'] ?? ''
                )
            );
        }

        return new Stream(stream: $resource);
    }

    public function createStreamFromResource($resource): StreamInterface
    {
        return new Stream(stream: $resource);
    }

    public function createUploadedFile(
        StreamInterface $stream,
        ?int $size = null,
        int $error = UPLOAD_ERR_OK,
        ?string $clientFilename = null,
        ?string $clientMediaType = null
    ): UploadedFileInterface {
        if (null === $size) {
            $size = $stream->getSize();
        }

        return new UploadedFile($stream, $size, $error, $clientFilename, $clientMediaType);
    }

    /**
     * @throws MalformedUrlException
     */
    public function createUri(string $uri = ''): UriInterface
    {
        return new Url(uri: $uri);
    }
}
