<?php

/**
 * Qubus\Http
 *
 * @link       https://github.com/QubusPHP/http
 * @copyright  2020 Joshua Parker <josh@joshuaparker.blog>
 * @license    https://opensource.org/licenses/mit-license.php MIT License
 *
 * @since      1.0.0
 */

declare(strict_types=1);

namespace Qubus\Http;

use JsonSerializable;
use Laminas\Diactoros\Uri;
use Psr\Http\Message\UriInterface;
use Qubus\Http\Exception\MalformedUrlException;

use function array_diff_key;
use function array_filter;
use function array_flip;
use function array_key_exists;
use function array_map;
use function array_merge;
use function count;
use function http_build_query;
use function parse_str;
use function parse_url;
use function preg_replace_callback;
use function rtrim;
use function sprintf;
use function stripos;
use function strtolower;
use function trim;
use function urlencode;

class Url extends Uri implements UriInterface, JsonSerializable
{
    private null|string $originalUrl = null;

    private string $scheme = '';

    private string $username = '';

    private string $password = '';

    private string $host = '';

    private null|int|string $port = null;

    private string $path = '';

    /** @var array $params */
    private array $params = [];

    private null|string $fragment = null;

    /**
     * @throws MalformedUrlException
     */
    public function __construct(string $uri = '')
    {
        $this->originalUrl = $uri;

        if ($uri !== null && $uri !== '/') {
            $data = $this->parseUrl($uri);

            $this->scheme = $data['scheme'] ?? null;
            $this->host = $data['host'] ?? null;
            $this->port = $data['port'] ?? null;
            $this->username = isset($data['user']) ? $this->withUserInfo($data['user']) : '';
            $this->password = isset($data['pass']) ? ':' . $data['pass'] : '';

            if (isset($data['path']) === true) {
                $this->setPath($data['path']);
            }

            $this->fragment = $data['fragment'] ?? null;

            if (isset($data['query']) === true) {
                $this->setQueryString($data['query']);
            }
        }

        parent::__construct($uri);
    }

    /**
     * Check if url is using a secure protocol like https
     */
    public function isSecure(): bool
    {
        return strtolower($this->getScheme()) === 'https';
    }

    /**
     * Checks if url is relative
     */
    public function isRelative(): bool
    {
        return $this->getHost() === null;
    }

    /**
     * Get path from url
     */
    public function getPath(): string
    {
        return $this->path ?? '/';
    }

    /**
     * Set the url path
     *
     * @return static
     */
    public function setPath(string $path): self
    {
        $this->path = rtrim($path, '/') . '/';

        return $this;
    }

    /**
     * Get query-string from url
     *
     * @return array
     */
    public function getParams(): array
    {
        return $this->params;
    }

    /**
     * Merge parameters array
     *
     * @param array $params
     * @return static
     */
    public function mergeParams(array $params): self
    {
        return $this->setParams(array_merge($this->getParams(), $params));
    }

    /**
     * Set the url params
     *
     * @param array $params
     * @return static
     */
    public function setParams(array $params): self
    {
        $this->params = $params;

        return $this;
    }

    /**
     * Set raw query-string parameters as string
     *
     * @return static
     */
    public function setQueryString(string $queryString): self
    {
        $params = [];

        if (parse_str($queryString, $params) !== false) {
            return $this->setParams($params);
        }

        return $this;
    }

    /**
     * Get query-string params as string
     */
    public function getQueryString(): string
    {
        return static::arrayToParams($this->getParams());
    }

    /**
     * Get fragment from url (everything after #)
     */
    public function getFragment(): string
    {
        return $this->fragment;
    }

    /**
     * Set url fragment
     *
     * @return static
     */
    public function setFragment(string $fragment): self
    {
        $this->fragment = $fragment;

        return $this;
    }

    public function getOriginalUrl(): string
    {
        return $this->originalUrl;
    }

    /**
     * Get position of value.
     * Returns -1 on failure.
     */
    public function indexOf(string $value): int
    {
        $index = stripos($this->getOriginalUrl(), $value);

        return $index === false ? -1 : $index;
    }

    /**
     * Check if url contains value.
     */
    public function contains(string $value): bool
    {
        return stripos($this->getOriginalUrl(), $value) !== false;
    }

    /**
     * Check if url contains parameter/query string.
     */
    public function hasParam(string $name): bool
    {
        return array_key_exists($name, $this->getParams());
    }

    /**
     * Removes multiple parameters from the query-string
     *
     * @param array ...$names
     * @return static
     */
    public function removeParams(...$names): self
    {
        $params = array_diff_key($this->getParams(), array_flip($names));
        $this->setParams($params);

        return $this;
    }

    /**
     * Removes parameter from the query-string
     *
     * @return static
     */
    public function removeParam(string $name): self
    {
        $params = $this->getParams();
        unset($params[$name]);
        $this->setParams($params);

        return $this;
    }

    /**
     * Get parameter by name.
     * Returns parameter value or default value.
     */
    public function getParam(string $name, ?string $defaultValue): ?string
    {
        return $this->getParams()[$name] ?? $defaultValue;
    }

    /**
     * UTF-8 aware parse_url() replacement.
     *
     * @param string $url
     * @param int $component
     * @return array
     * @throws MalformedUrlException
     */
    public function parseUrl(string $url, int $component = -1): array
    {
        $encodedUrl = preg_replace_callback(
            '/[^:\/@?&=#]+/u',
            fn ($matches) => urlencode($matches[0]),
            $url
        );

        $parts = parse_url($encodedUrl, $component);

        if ($parts === false) {
            throw new MalformedUrlException(sprintf('Failed to parse url: "%s"', $url));
        }

        return array_map('urldecode', $parts);
    }

    /**
     * Convert array to query-string params
     *
     * @param array $getParams
     * @param bool $includeEmpty
     * @return string
     */
    public static function arrayToParams(array $getParams = [], bool $includeEmpty = true): string
    {
        if (count($getParams) !== 0) {
            if ($includeEmpty === false) {
                $getParams = array_filter($getParams, function ($item) {
                    return trim($item) !== '';
                });
            }

            return http_build_query($getParams);
        }

        return '';
    }

    /**
     * Returns the relative url
     */
    public function getRelativeUrl(): string
    {
        $params = $this->getQueryString();

        $path     = $this->path ?? '';
        $query    = $params !== '' ? '?' . $params : '';
        $fragment = isset($this->fragment) ? '#' . $this->fragment : '';

        return $path . $query . $fragment;
    }

    /**
     * Returns the absolute url
     */
    public function getAbsoluteUrl(): string
    {
        $scheme = $this->scheme !== null ? $this->scheme . '://' : '';
        $host = $this->host ?? '';
        $port = $this->port !== null ? ':' . $this->port : '';
        $user = $this->username ?? '';
        $pass = $this->password !== '' ? ':' . $this->password : '';
        $pass = $user || $pass ? $pass . '@' : '';

        return $scheme . $user . $pass . $host . $port . $this->getRelativeUrl();
    }

    /**
     * Specify data which should be serialized to JSON
     *
     * @link http://php.net/manual/en/jsonserializable.jsonserialize.php
     *
     * @return string data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     * @since 5.4.0
     */
    public function jsonSerialize(): string
    {
        return $this->getRelativeUrl();
    }

    public function __toString(): string
    {
        return $this->getRelativeUrl();
    }
}
