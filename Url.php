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
use Laminas\Diactoros\Exception\InvalidArgumentException as DiactorosInvalidArgumentException;
use Laminas\Diactoros\Uri;
use Psr\Http\Message\UriInterface;
use Qubus\Http\Exception\MalformedUrlException;
use SensitiveParameter;

use function array_diff_key;
use function array_filter;
use function array_flip;
use function array_key_exists;
use function array_merge;
use function count;
use function explode;
use function http_build_query;
use function is_scalar;
use function is_string;
use function parse_str;
use function parse_url;
use function sprintf;
use function str_contains;
use function stripos;
use function strtolower;
use function trim;

class Url extends Uri implements UriInterface, JsonSerializable
{
    public readonly null|string $originalUrl;

    // phpcs:disable PSR2.Classes.PropertyDeclaration.Multiple
    // phpcs:disable PSR2.Classes.PropertyDeclaration.ScopeMissing
    // phpcs:disable Generic.WhiteSpace.ScopeIndent.IncorrectExact
    public ?string $username {
        get => $this->getUsername();
    }
    // phpcs:enable

    /**
     * Original path with no sanitization to ending slash.
     *
     * @var string|null
     */
    private readonly ?string $originalPath;

    /**
     * @throws MalformedUrlException
     */
    public function __construct(string $uri = '')
    {
        try {
            parent::__construct($uri);
        } catch (DiactorosInvalidArgumentException $exception) {
            throw new MalformedUrlException(
                message: sprintf('Failed to parse url: "%s"', $uri),
                previous: $exception
            );
        }

        $this->originalUrl = $uri;
        $parts = $this->parseUrl($uri);
        $this->originalPath = $parts['path'] ?? null;
    }

    /**
     * @throws MalformedUrlException
     */
    public function parse(?string $url = null, bool $originalPath = false): self
    {
        if ($url === null || $url === '') {
            return $this;
        }

        return new self($url);
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
        return $this->getHost() === '';
    }

    /**
     * Set the username of the url
     *
     * @param string $username
     * @return static
     */
    public function withUsername(string $username): self
    {
        return parent::withUserInfo($username, $this->getPassword());
    }

    /**
     * Set the url password
     *
     * @param string $password
     * @return static
     */
    public function withPassword(#[SensitiveParameter] string $password): self
    {
        return parent::withUserInfo($this->getUsername() ?? '', $password);
    }

    public function withUserInfo(
        string $user,
        #[SensitiveParameter]
        ?string $password = null
    ): self {
        return parent::withUserInfo($user, $password);
    }

    /**
     * Set the url path
     *
     * @return static
     */
    public function withPath(string $path): self
    {
        return parent::withPath($path);
    }

    public function withQuery(string $query): self
    {
        return parent::withQuery($query);
    }

    /**
     * Merge parameters array
     *
     * @param array $params
     * @return static
     */
    public function mergeParams(array $params): self
    {
        return $this->withParams(array_merge($this->getParams(), $params));
    }

    /**
     * Set the url params
     *
     * @param array $params
     * @return static
     */
    public function withParams(array $params): self
    {
        return $this->withQuery(self::arrayToParams($params));
    }

    /**
     * Set raw query-string parameters as string
     *
     * @return static
     */
    public function withQueryString(string $queryString): self
    {
        return $this->withQuery($queryString);
    }

    /**
     * Get query-string params as string
     */
    public function getQueryString(): string
    {
        return static::arrayToParams($this->getParams());
    }

    public function getOriginalUrl(): ?string
    {
        return $this->originalUrl;
    }

    public function getOriginalPath(): ?string
    {
        return $this->originalPath;
    }

    public function getParams(): array
    {
        return self::queryStringToArray($this->getQuery());
    }

    /**
     * Get fragment from url (everything after #)
     */
    public function getFragment(): string
    {
        return parent::getFragment();
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
        return array_key_exists($name, $this->getParams());
    }

    /**
     * Removes multiple parameters from the query-string
     *
     * @param int|string ...$names
     * @return static
     */
    public function removeParams(...$names): self
    {
        $params = array_diff_key($this->getParams(), array_flip($names));

        return $this->withParams($params);
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

        return $this->withParams($params);
    }

    /**
     * Get parameter by name.
     * Returns parameter value or default value.
     */
    public function getParam(string $name, ?string $defaultValue = null): ?string
    {
        $params = $this->getParams();
        $value = $params[$name] ?? $defaultValue;

        return is_scalar($value) ? (string) $value : $defaultValue;
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
        $parts = parse_url($url, $component);

        if ($parts === false) {
            throw new MalformedUrlException(message: sprintf('Failed to parse url: "%s"', $url));
        }

        if ($component === -1) {
            return $parts;
        }

        $key = match ($component) {
            PHP_URL_SCHEME => 'scheme',
            PHP_URL_HOST => 'host',
            PHP_URL_PORT => 'port',
            PHP_URL_USER => 'user',
            PHP_URL_PASS => 'pass',
            PHP_URL_PATH => 'path',
            PHP_URL_QUERY => 'query',
            PHP_URL_FRAGMENT => 'fragment',
            default => 'component',
        };

        return [$key => $parts];
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
                $getParams = array_filter(
                    array: $getParams,
                    callback: static fn ($item): bool => ! is_scalar($item)
                        || ! is_string($item)
                        || trim($item) !== ''
                );
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
        $path = $this->getPath();

        if ($includeParams === false) {
            return $path;
        }

        $query = $this->getQueryString() !== '' ? '?' . $this->getQueryString() : '';
        $fragment = $this->getFragment() !== '' ? '#' . $this->getFragment() : '';

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
        if ($includeParams) {
            return parent::__toString();
        }

        return $this->withQuery('')->withFragment('')->__toString();
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
        return $this->__toString();
    }

    public function __toString(): string
    {
        return parent::__toString();
    }

    private static function queryStringToArray(string $queryString): array
    {
        $params = [];
        parse_str($queryString, $params);

        return $params;
    }

    private function getUsername(): ?string
    {
        $userInfo = $this->getUserInfo();

        if ($userInfo === '') {
            return null;
        }

        return explode(':', $userInfo, 2)[0];
    }

    private function getPassword(): ?string
    {
        $userInfo = $this->getUserInfo();

        if (! str_contains($userInfo, ':')) {
            return null;
        }

        return explode(':', $userInfo, 2)[1];
    }
}
