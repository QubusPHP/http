<?php

declare(strict_types=1);

namespace Qubus\Http\Swoole\Factory;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UploadedFileFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Qubus\Http\Swoole\Request;
use Qubus\Http\Swoole\ServerRequest;
use Swoole\Http\Request as SwooleRequest;

readonly class RequestFactory implements PsrSwooleFactory
{
    public function __construct(
        private UriFactoryInterface $uriFactory,
        private StreamFactoryInterface $streamFactory,
        private UploadedFileFactoryInterface $uploadedFileFactory
    ) {
    }

    public function createRequest(SwooleRequest $swooleRequest): Request
    {
        return new Request(
            $swooleRequest,
            $this->uriFactory,
            $this->streamFactory
        );
    }

    public function createServerRequest(SwooleRequest $swooleRequest): ServerRequestInterface
    {
        return new ServerRequest(
            $swooleRequest,
            $this->uriFactory,
            $this->streamFactory,
            $this->uploadedFileFactory
        );
    }
}
