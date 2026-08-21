<?php

declare(strict_types=1);

namespace Qubus\Http\Swoole;

use InvalidArgumentException;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UploadedFileFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;
use Swoole\Http\Request as SwooleRequest;

use function array_change_key_case;
use function is_array;
use function is_object;
use function Qubus\Support\Helpers\is_null__;

use const CASE_UPPER;

class ServerRequest extends Request implements ServerRequestInterface
{
    private array $attributes;
    private array $cookies;
    private array $files;
    private null|array|object $parsedBody = null;
    private bool $parsedBodyIsSet = false;
    private array $query;

    public function __construct(
        SwooleRequest $swooleRequest,
        UriFactoryInterface $uriFactory,
        StreamFactoryInterface $streamFactory,
        private readonly UploadedFileFactoryInterface $uploadedFileFactory
    ) {
        parent::__construct($swooleRequest, $uriFactory, $streamFactory);
    }

    public function getServerParams(): array
    {
        return array_change_key_case($this->swooleRequest->server ?? [], CASE_UPPER);
    }

    public function getCookieParams(): array
    {
        return $this->cookies ?? ($this->swooleRequest->cookie ?? []);
    }

    public function withCookieParams(array $cookies): ServerRequestInterface
    {
        $new = clone $this;
        $new->cookies = $cookies;
        return $new;
    }

    public function getQueryParams(): array
    {
        return $this->query ?? ($this->swooleRequest->get ?? []);
    }

    public function withQueryParams(array $query): ServerRequestInterface
    {
        $new = clone $this;
        $new->query = $query;
        return $new;
    }

    public function getUploadedFiles(): array
    {
        if (isset($this->files)) {
            return $this->files;
        }

        $files = array_map(function ($fileData) {
            return $this->uploadedFileFactory->createUploadedFile(
                $this->streamFactory->createStreamFromFile($fileData['tmp_name']),
                $fileData['size'],
                $fileData['error'],
                $fileData['name'],
                $fileData['type']
            );
        }, $this->swooleRequest->files ?? []);

        return $this->files = $files;
    }

    public function withUploadedFiles(array $uploadedFiles): ServerRequestInterface
    {
        $new = clone $this;
        $new->files = $uploadedFiles;
        return $new;
    }

    public function getParsedBody(): object|array|null
    {
        if ($this->parsedBodyIsSet) {
            return $this->parsedBody;
        }

        if (!empty($this->swooleRequest->post)) {
            return $this->swooleRequest->post;
        }

        return null;
    }

    public function withParsedBody($data): ServerRequestInterface
    {
        if (!is_object($data) && !is_array($data) && !is_null__($data)) {
            throw new InvalidArgumentException(message: 'Unsupported argument type');
        }

        $new = clone $this;
        $new->parsedBody = $data;
        $new->parsedBodyIsSet = true;
        return $new;
    }

    public function getAttributes(): array
    {
        return $this->attributes ?? [];
    }

    public function getAttribute(string $name, $default = null)
    {
        return $this->attributes[$name] ?? $default;
    }

    public function withAttribute(string $name, $value): ServerRequestInterface
    {
        $new = clone $this;
        $new->attributes[$name] = $value;
        return $new;
    }

    public function withoutAttribute(string $name): ServerRequestInterface
    {
        $new = clone $this;
        unset($new->attributes[$name]);
        return $new;
    }
}
