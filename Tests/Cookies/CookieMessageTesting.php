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

use Psr\Http\Message\StreamInterface;
use RuntimeException;

use function implode;

trait CookieMessageTesting
{
    /** @var string[] */
    private $headers = [];

    /** {@inheritDoc} */
    public function getProtocolVersion(): void
    {
        throw new RuntimeException('This method has not been implemented.');
    }

    /** {@inheritDoc} */
    public function withProtocolVersion($version): void
    {
        throw new RuntimeException('This method has not been implemented.');
    }

    /** {@inheritDoc} */
    public function hasHeader($name): void
    {
        throw new RuntimeException('This method has not been implemented.');
    }

    /** {@inheritDoc} */
    public function withHeader($name, $value)
    {
        $clone = clone $this;

        $clone->headers[$name] = [$value];

        return $clone;
    }

    /** {@inheritDoc} */
    public function withAddedHeader($name, $value)
    {
        $clone = clone $this;

        if (! isset($clone->headers[$name])) {
            $clone->headers[$name] = [];
        }

        $clone->headers[$name][] = $value;

        return $clone;
    }

    /** {@inheritDoc} */
    public function withoutHeader($name)
    {
        $clone = clone $this;

        if (isset($clone->headers[$name])) {
            unset($clone->headers[$name]);
        }

        return $clone;
    }

    /** {@inheritDoc} */
    public function getBody(): void
    {
        throw new RuntimeException('This method has not been implemented.');
    }

    /** {@inheritDoc} */
    public function withBody(StreamInterface $body): void
    {
        throw new RuntimeException('This method has not been implemented.');
    }

    /** {@inheritDoc} */
    public function getHeaders(): void
    {
        throw new RuntimeException('This method has not been implemented.');
    }

    /** {@inheritDoc} */
    public function getHeader($name)
    {
        if (! isset($this->headers[$name])) {
            return [];
        }

        return $this->headers[$name];
    }

    /** {@inheritDoc} */
    public function getHeaderLine($name)
    {
        return implode(',', $this->headers[$name]);
    }

    /** {@inheritDoc} */
    public function getHeaderLines($name)
    {
        if (! isset($this->headers[$name])) {
            return [];
        }

        return $this->headers[$name];
    }
}
