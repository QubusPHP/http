<?php

/**
 * Qubus\Http
 *
 * @link       https://github.com/QubusPHP/http
 * @copyright  2020 Joshua Parker <josh@joshuaparker.blog>
 * @copyright  2015 Beau Simensen <beau@dflydev.com>
 * @license    https://opensource.org/licenses/mit-license.php MIT License
 *
 * @since      1.0.0
 */

declare(strict_types=1);

namespace Qubus\Tests\Http\Cookies;

use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\StreamInterface;
use RuntimeException;

use function array_values;
use function implode;
use function is_array;

trait CookieMessageTesting
{
    /** @var string[] */
    private array $headers = [];

    /** {@inheritDoc} */
    public function getProtocolVersion(): string
    {
        throw new RuntimeException('This method has not been implemented.');
    }

    /** {@inheritDoc} */
    public function withProtocolVersion($version): MessageInterface
    {
        throw new RuntimeException('This method has not been implemented.');
    }

    /** {@inheritDoc} */
    public function hasHeader($name): bool
    {
        throw new RuntimeException('This method has not been implemented.');
    }

    /** {@inheritDoc} */
    public function withHeader($name, $value): CookieRequestTesting|CookieResponseTesting
    {
        $clone = clone $this;

        $clone->headers[$name] = is_array($value) ? array_values($value) : [$value];

        return $clone;
    }

    /** {@inheritDoc} */
    public function withAddedHeader($name, $value): CookieRequestTesting|CookieResponseTesting
    {
        $clone = clone $this;

        if (! isset($clone->headers[$name])) {
            $clone->headers[$name] = [];
        }

        $clone->headers[$name][] = $value;

        return $clone;
    }

    /** {@inheritDoc} */
    public function withoutHeader($name): CookieRequestTesting|CookieResponseTesting
    {
        $clone = clone $this;

        if (isset($clone->headers[$name])) {
            unset($clone->headers[$name]);
        }

        return $clone;
    }

    /** {@inheritDoc} */
    public function getBody(): StreamInterface
    {
        throw new RuntimeException('This method has not been implemented.');
    }

    /** {@inheritDoc} */
    public function withBody(StreamInterface $body): MessageInterface
    {
        throw new RuntimeException('This method has not been implemented.');
    }

    /** {@inheritDoc} */
    public function getHeaders(): array
    {
        throw new RuntimeException('This method has not been implemented.');
    }

    /** {@inheritDoc} */
    public function getHeader($name): array
    {
        if (! isset($this->headers[$name])) {
            return [];
        }

        return $this->headers[$name];
    }

    /** {@inheritDoc} */
    public function getHeaderLine($name): string
    {
        return implode(',', $this->headers[$name]);
    }

    public function getHeaderLines($name): array|string
    {
        if (! isset($this->headers[$name])) {
            return [];
        }

        return $this->headers[$name];
    }
}
