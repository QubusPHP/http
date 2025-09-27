<?php

/**
 * Qubus\Http
 *
 * @link       https://github.com/QubusPHP/http
 * @copyright  2020
 * @author     Joshua Parker <joshua@joshuaparker.dev>
 * @license    https://opensource.org/licenses/mit-license.php MIT License
 */

declare(strict_types=1);

namespace Qubus\Http;

use JsonSerializable;
use Laminas\Diactoros\Uri;
use Psr\Http\Message\UriInterface;
use Qubus\Http\Exception\MalformedUrlException;
use SensitiveParameter;

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
    //phpcs:disable
    public null|string $originalUrl = null {
        get => $this->originalUrl;
    }

    private string $scheme = '';

    public ?string $username = null {
        get => $this->username;
    }

    private ?string $password = null {
        get => $this->password;
    }

    private string $host = '';

    private ?int $port = null;

    private string $path = '';

    /**
     * Original path with no sanitization to ending slash.
     *
     * @var string|null
     */
    private ?string $originalPath = null {
        get => $this->originalPath;
    }

    /** @var array $params */
    private array $params = [] {
        get => $this->params;
    }

    private string $fragment = '';
    //phpcs:enable

    /**
     * @throws MalformedUrlException
     */
    public function __construct(string $uri = '')
    {
        $this->originalUrl = $uri;
        $this->parseUrl($uri);

        parent::__construct($uri);
    }

    /**
     * @throws MalformedUrlException
     */
    public function parse(?string $url = null, bool $originalPath = false): self
    {
        if ($url !== null && $url !== '') {
            $data = $this->parseUrl($url);

            $this->scheme = $data['scheme'] ?? null;
            $this->host = $data['host'] ?? null;
            $this->port = $data['port'] ?? null;
            $this->username = $data['user'] ?? null;
            $this->password = $data['pass'] ?? null;

            if (isset($data['path']) === true) {
                $this->withPath($data['path']);

                if ($originalPath === true) {
                    $this->originalPath = $data['path'];
                }
            }

            $this->fragment = $data['fragment'] ?? null;

            if (isset($data['query']) === true) {
                $this->withQueryString($data['query']);
            }
        }

        return $this;
    }

    /**
     * Check if url is using a secure protocol like https.
     */
    public function isSecure(): bool
    {
        return strtolower($this->getScheme()) === 'https';
    }

    /**
     * Checks if url is relative.
     */
    public function isRelative(): bool
    {
        return $this->getHost() === null;
    }

    /**
     * Set the username of the url
     *
     * @param string $username
     * @return static
     */
    public function withUsername(string $username): self
    {
        $new           = clone $this;
        $new->username = $username;

        return $new;
    }

    /**
     * Set the url password
     *
     * @param string $password
     * @return static
     */
    public function withPassword(#[SensitiveParameter] string $password): self
    {
        $new           = clone $this;
        $new->password = $password;

        return $new;
    }

    /**
     * Set the url path
     *
     * @return static
     */
    public function withPath(string $path): self
    {
        $new       = clone $this;
        $new->path = rtrim($path, '/') . '/';

        return $new;
    }

    /**
     * Merge parameters array
     *
     * @param array $params
     * @return static
     */
    public function mergeParams(array $params): self
    {
        return $this->withParams(array_merge($this->params, $params));
    }

    /**
     * Set the url params
     *
     * @param array $params
     * @return static
     */
    public function withParams(array $params): self
    {
        $new         = clone $this;
        $new->params = $params;

        return $new;
    }

    /**
     * Set raw query-string parameters as string
     *
     * @return static
     */
    public function withQueryString(string $queryString): self
    {
        $params = [];

        if (parse_str($queryString, $params) !== false) {
            return $this->withParams($params);
        }

        return $this;
    }

    /**
     * Get query-string params as string
     */
    public function getQueryString(): string
    {
        return static::arrayToParams($this->params);
    }

    /**
     * Get fragment from url (everything after #)
     */
    public function getFragment(): string
    {
        return $this->fragment;
    }

    /**
     * Get position of value.
     * Returns -1 on failure.
     */
    public function indexOf(string $value): int
    {
        $index = stripos($this->originalUrl, $value);

        return $index === false ? -1 : $index;
    }

    /**
     * Check if url contains value.
     */
    public function contains(string $value): bool
    {
        return stripos($this->originalUrl, $value) !== false;
    }

    /**
     * Check if url contains parameter/query string.
     */
    public function hasParam(string $name): bool
    {
        return array_key_exists($name, $this->params);
    }

    /**
     * Removes multiple parameters from the query-string
     *
     * @param int[]|string[] ...$names
     * @return static
     */
    public function removeParams(...$names): self
    {
        $params = array_diff_key($this->params, array_flip($names));

        return $this->withParams($params);
    }

    /**
     * Removes parameter from the query-string
     *
     * @return static
     */
    public function removeParam(string $name): self
    {
        $params = $this->params;
        unset($params[$name]);

        return $this->withParams($params);
    }

    /**
     * Get parameter by name.
     * Returns parameter value or default value.
     */
    public function getParam(string $name, ?string $defaultValue = null): ?string
    {
        return (isset($this->params[$name]) === true) ? $this->params[$name] : $defaultValue;
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
            static fn ($matches): string => urlencode($matches[0]),
            $url
        );

        $parts = parse_url($encodedUrl, $component);

        if ($parts === false) {
            throw new MalformedUrlException(message: sprintf('Failed to parse url: "%s"', $url));
        }

        return array_map(callback: 'urldecode', array: $parts);
    }

    /**
     * Convert array to query-string params.
     *
     * @param array $getParams
     * @param bool $includeEmpty
     * @return string
     */
    public static function arrayToParams(array $getParams = [], bool $includeEmpty = true): string
    {
        if (count($getParams) !== 0) {
            if ($includeEmpty === false) {
                $getParams = array_filter(array: $getParams, callback: static fn ($item): bool => trim($item) !== '');
            }

            return http_build_query(data: $getParams);
        }

        return '';
    }

    /**
     * Returns the relative url
     *
     * @param bool $includeParams
     * @return string
     */
    public function getRelativeUrl(bool $includeParams = true): string
    {
        $path = $this->path ?? '/';

        if ($includeParams === false) {
            return $path;
        }

        $query = $this->getQueryString() !== '' ? '?' . $this->getQueryString() : '';
        $fragment = $this->fragment !== '' ? '#' . $this->fragment : '';

        return $path . $query . $fragment;
    }

    /**
     * Returns the absolute url
     *
     * @param bool $includeParams
     * @return string
     */
    public function getAbsoluteUrl(bool $includeParams = true): string
    {
        $scheme = $this->scheme !== '' ? $this->scheme . '://' : '';
        $host = $this->host ?? '';
        $port = $this->port !== null ? ':' . $this->port : '';
        $user = $this->username ?? '';
        $pass = $this->password !== null ? ':' . $this->password : '';
        $pass = ($user !== '' || $pass !== '') ? $pass . '@' : '';

        return $scheme . $user . $pass . $host . $port . $this->getRelativeUrl($includeParams);
    }

    /**
     * Specify data which should be serialized to JSON.
     *
     * @link http://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return string Data which can be serialized by <b>json_encode</b>,
     *                which is a value of any type other than a resource.
     */
    public function jsonSerialize(): string
    {
        return $this->getHost() . $this->getRelativeUrl();
    }

    public function __toString(): string
    {
        return $this->getHost() . $this->getRelativeUrl();
    }
}
