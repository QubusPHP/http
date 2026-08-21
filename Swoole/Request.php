<?php

declare(strict_types=1);

namespace Qubus\Http\Swoole;

use InvalidArgumentException;
use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\UriFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UriInterface;
use Psr\Http\Message\StreamInterface;
use Swoole\Http\Request as SwooleRequest;

use function array_map;
use function array_merge;
use function base64_decode;
use function explode;
use function implode;
use function is_array;
use function preg_match;
use function str_contains;
use function str_starts_with;
use function strtolower;

class Request implements RequestInterface
{
    private StreamInterface $body;
    private ?array $headers = null;
    private string $method;
    private string $protocol = '1.1';
    private string $requestTarget;
    private UriInterface $uri;

    public function __construct(
        public readonly SwooleRequest $swooleRequest,
        protected readonly UriFactoryInterface $uriFactory,
        protected readonly StreamFactoryInterface $streamFactory
    ) {
    }

    public function getRequestTarget(): string
    {
        return !empty($this->requestTarget)
        ? $this->requestTarget
        : ($this->requestTarget = $this->buildRequestTarget())
        ;
    }

    private function buildRequestTarget(): string
    {
        $path = $this->swooleRequest->server['request_uri'] ?? '/';
        $queryString = ! empty($this->swooleRequest->server['query_string']) && ! str_contains($path, '?')
        ? '?' . $this->swooleRequest->server['query_string']
        : '';

        return $path . $queryString;
    }

    public function withRequestTarget(string $requestTarget): RequestInterface
    {
        $new = clone $this;
        $new->requestTarget = $requestTarget;
        return $new;
    }

    public function getMethod(): string
    {
        return !empty($this->method)
        ? $this->method
        : ($this->method = $this->swooleRequest->server['request_method']);
    }

    public function withMethod(string $method): RequestInterface
    {
        if ($method === '' || ! preg_match('/^[!#$%&\'*+.^_`|~0-9A-Za-z-]+$/D', $method)) {
            throw new InvalidArgumentException(message: 'Invalid HTTP method');
        }

        $new = clone $this;
        $new->method = $method;
        return $new;
    }

    public function getUri(): UriInterface
    {
        if (!empty($this->uri)) {
            return $this->uri;
        }

        $userInfo = $this->parseUserInfo();
        $scheme = $this->swooleRequest->server['request_scheme'] ?? 'http';
        $host = $this->swooleRequest->header['host']
        ?? $this->swooleRequest->server['server_name']
        ?? 'localhost';

        $uri = $scheme . '://' . ($userInfo !== null ? $userInfo . '@' : '') . $host . $this->getRequestTarget();

        return $this->uri = $this->uriFactory->createUri(
            $uri
        );
    }

    private function parseUserInfo(): ?string
    {
        $authorization = $this->swooleRequest->header['authorization'] ?? '';

        if (str_starts_with(strtolower((string) $authorization), 'basic ')) {
            $parts = explode(' ', (string) $authorization, 2);
            $decoded = base64_decode($parts[1] ?? '', true);

            return $decoded !== false ? $decoded : null;
        }

        return null;
    }

    public function withUri(UriInterface $uri, bool $preserveHost = false): RequestInterface
    {
        $new = clone $this;
        $new->uri = $uri;

        return $new->shouldUpdateHostHeader($preserveHost)
        ? $new->withHeader('host', $uri->getHost())
        : $new;
    }

    private function shouldUpdateHostHeader($preserveHost): bool
    {
        return !empty($this->uri->getHost())
        && (!$preserveHost || !$this->hasHeader('host'));
    }

    public function getProtocolVersion(): string
    {
        return $this->protocol;
    }

    public function withProtocolVersion(string $version): MessageInterface
    {
        $new = clone $this;
        $new->protocol = $version;
        return $new;
    }

    public function getHeaders(): array
    {
        $headers = is_array($this->headers)
        ? $this->headers
        : $this->swooleRequest->header;
        return array_map(fn($value) => is_array($value) ? $value : [$value], $headers);
    }

    public function hasHeader($name): bool
    {
        return $this->findHeaderName((string) $name) !== null;
    }

    private function initHeadersList(): void
    {
        if (is_array($this->headers)) {
            return;
        }

        $this->headers = $this->swooleRequest->header;
    }

    public function getHeader(string $name): array
    {
        if (!$this->hasHeader($name)) {
            return [];
        }

        foreach ($this->headers as $key => $value) {
            if (strtolower($name) == strtolower($key)) {
                return is_array($value)
                ? $value
                : [$value];
            }
        }

        return [];
    }

    public function getHeaderLine(string $name): string
    {
        return implode(',', $this->getHeader($name));
    }

    public function withHeader(string $name, $value): MessageInterface
    {
        $new = clone $this;
        $new->initHeadersList();

        $new->headers[$name] = $value;

        return $new;
    }

    public function withAddedHeader(string $name, $value): MessageInterface
    {
        if (!$this->hasHeader($name)) {
            return $this->withHeader($name, $value);
        }

        $new = clone $this;
        $new->initHeadersList();
        $headerName = $new->findHeaderName($name) ?? $name;
        $values = is_array($value) ? $value : [$value];

        $existing = is_array($new->headers[$headerName])
        ? $new->headers[$headerName]
        : [$new->headers[$headerName]];
        $new->headers[$headerName] = array_merge($existing, $values);

        return $new;
    }

    public function withoutHeader(string $name): MessageInterface
    {
        $new = clone $this;

        if (!$new->hasHeader($name)) {
            return $new;
        }

        foreach ($new->headers as $key => $value) {
            if (strtolower($name) === strtolower($key)) {
                unset($new->headers[$key]);
                return $new;
            }
        }

        return $new;
    }

    public function getBody(): StreamInterface
    {
        return $this->body ??= $this->streamFactory->createStream((string) $this->swooleRequest->rawContent());
    }

    public function withBody(StreamInterface $body): MessageInterface
    {
        $new = clone $this;
        $new->body = $body;
        return $new;
    }

    private function findHeaderName(string $name): ?string
    {
        $this->initHeadersList();

        return array_find_key($this->headers, fn($value, $headerName) => strtolower($name) === strtolower($headerName));
    }
}
