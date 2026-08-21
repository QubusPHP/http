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

use InvalidArgumentException;
use Laminas\Diactoros\Request as BaseRequest;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;
use Qubus\Http\Exception\MalformedUrlException;
use Qubus\Http\Input\Handler;

use function array_key_exists;
use function array_map;
use function explode;
use function gethostbyname;
use function in_array;
use function is_numeric;
use function is_string;
use function preg_match_all;
use function rtrim;
use function str_replace;
use function stripos;
use function strpos;
use function trim;
use function strtolower;
use function strtoupper;

final class Request extends BaseRequest implements RequestInterface
{
    public const string REQUEST_TYPE_GET = 'get';
    public const string REQUEST_TYPE_POST = 'post';
    public const string REQUEST_TYPE_PUT = 'put';
    public const string REQUEST_TYPE_PATCH = 'patch';
    public const string REQUEST_TYPE_OPTIONS = 'options';
    public const string REQUEST_TYPE_DELETE = 'delete';
    public const string REQUEST_TYPE_HEAD = 'head';
    public const string CONTENT_TYPE_JSON = 'application/json';
    public const string CONTENT_TYPE_FORM_DATA = 'multipart/form-data';
    public const string CONTENT_TYPE_X_FORM_ENCODED = 'application/x-www-form-urlencoded';

    public const string FORCE_METHOD_KEY = '_method';

    /**
     * All request-types
     * @var string[]
     */
    public static array $requestTypes = [
        self::REQUEST_TYPE_GET,
        self::REQUEST_TYPE_POST,
        self::REQUEST_TYPE_PUT,
        self::REQUEST_TYPE_PATCH,
        self::REQUEST_TYPE_OPTIONS,
        self::REQUEST_TYPE_DELETE,
        self::REQUEST_TYPE_HEAD,
    ];

    /**
     * Post request-types.
     * @var string[]
     */
    public static array $requestTypesPost = [
        self::REQUEST_TYPE_POST,
        self::REQUEST_TYPE_PUT,
        self::REQUEST_TYPE_PATCH,
        self::REQUEST_TYPE_DELETE,
    ];

    /**
     * Additional data.
     *
     * @var array $data
     */
    private array $data = [];

    /**
     * Server headers.
     *
     * @var array $httpHeaders
     */
    protected array $httpHeaders = [];

    /**
     * Request ContentType
     * @var string
     */
    protected string $contentType = '';

    /**
     * Request host.
     */
    protected ?string $host = null;

    /**
     * Current request url.
     */
    protected ?Url $url = null;

    /**
     * Request method.
     */
    protected ?string $method = null;

    /**
     * Input handler.
     */
    protected ?Handler $inputHandler = null;

    /**
     * Defines if request has pending rewrite.
     */
    protected bool $hasPendingRewrite = false;

    /**
     * Rewrite url.
     */
    protected ?string $rewriteUrl = null;

    /**
     * @param null|string|UriInterface $uri URI for the request, if any.
     * @param null|string $method HTTP method for the request, if any.
     * @param string|resource|StreamInterface $body Message body, if any.
     * @param array $headers Headers for the message, if any.
     * @throws MalformedUrlException|InvalidArgumentException For any invalid value.
     */

    public function __construct($uri = null, ?string $method = null, $body = 'php://temp', array $headers = [])
    {
        foreach ($this->getServerArray() as $key => $value) {
            $this->httpHeaders[strtolower(string: $key)] = $value;
            $this->httpHeaders[strtolower(string: str_replace(search: '_', replace: '-', subject: $key))] = $value;
        }

        if ($uri === null) {
            // Check if special IIS header exist, otherwise use default.
            $path = $this->getHttpHeader(name: 'unencoded-url')
            ?? $this->getHttpHeader(name: 'request-uri')
            ?? '/';
            $host = $this->getHttpHeader(name: 'host');
            $scheme = $this->isSecure() ? 'https' : 'http';
            $uri = $host === null ? $path : $scheme . '://' . $host . $path;
        }

        $method ??= $this->getHttpHeader(name: 'request-method') ?? 'GET';
        $actualMethod = strtoupper($method);
        $overrideMethod = $_POST[self::FORCE_METHOD_KEY] ?? null;

        if (
            $actualMethod === 'POST'
            && is_string($overrideMethod)
            && $this->isValidHttpMethod($overrideMethod)
        ) {
            $method = $overrideMethod;
        }

        parent::__construct($uri, $method, $body, $headers);

        $this->setUrl(url: new Url(uri: (string) $this->getUri()));
        $this->setHost($this->getUri()->getHost() ?: $this->getHttpHeader(name: 'host'));
        $this->withContentType(
            contentType: $this->getHeaderLine('Content-Type') ?: (string) $this->getHttpHeader('content-type')
        );
        $this->setMethod(method: $this->getMethod());
        $this->inputHandler = new Handler(request: $this);
    }

    public function isSecure(bool $trustForwardedHeader = false): bool
    {
        $https = strtolower((string) $this->getHttpHeader(name: 'https'));

        return ($this->url?->isSecure() ?? false)
        || ($trustForwardedHeader && strtolower((string) $this->getHttpHeader('x-forwarded-proto')) === 'https')
        || ($https !== '' && $https !== 'off' && $https !== '0')
        || (int) $this->getHttpHeader(name: 'server-port') === 443;
    }

    public function getUrl(): Url
    {
        return $this->url;
    }

    /**
     * Copy url object.
     */
    public function getUrlCopy(): Url
    {
        return clone $this->url;
    }

    public function getHost(): ?string
    {
        return $this->host;
    }

    /**
     * Get http basic auth user.
     */
    public function getAuthUser(): ?string
    {
        return $this->getServer(name: 'php-auth-user');
    }

    /**
     * Get http basic auth password.
     */
    public function getAuthPassword(): ?string
    {
        return $this->getServer(name: 'php-auth-pw');
    }

    /**
     * Get all headers.
     *
     * @return array
     */
    public function getHttpHeaders(): array
    {
        return $this->httpHeaders;
    }

    /**
     * Get ip address.
     *
     * If $safeMode is false, this function will detect Proxys.
     * But the user can edit this header to whatever he wants!
     * https://stackoverflow.com/questions/3003145/how-to-get-the-client-ip-address-in-php#comment-25086804
     *
     * @param bool $safeMode When enabled, only safe non-spoofable
     *                       headers will be returned. Note this
     *                       can cause issues when using proxy.
     * @return string|null
     */
    public function getIp(bool $safeMode = false): ?string
    {
        $headers = [];
        if ($safeMode === false) {
            $headers = [
                'http-cf-connecting-ip',
                'http-client-ip',
                'http-x-forwarded-for',
            ];
        }

        $headers[] = 'remote-addr';

        return $this->getFirstHeader(headers: $headers);
    }

    /**
     * Get remote address/ip
     *
     * @alias static::getIp
     * @return string|null
     */
    public function getRemoteAddr(): ?string
    {
        return $this->getIp();
    }

    /**
     * Get referer.
     */
    public function getReferer(): ?string
    {
        return $this->getServer(name: 'http-referer');
    }

    /**
     * Get user agent.
     */
    public function getUserAgent(): ?string
    {
        return $this->getServer(name: 'http-user-agent');
    }

    /**
     * Get header value by name
     *
     * @param string      $name Name of the header.
     * @param string|null $defaultValue Value to be returned if header is not found.
     * @param bool        $tryParse     When enabled the method will try to find the header
     *                                  from both client (http) and server-side variants,
     *                                  if the header is not found.
     */
    public function getHttpHeader(string $name, ?string $defaultValue = null, bool $tryParse = true): ?string
    {
        $name = strtolower(str_replace('_', '-', $name));
        $header = $this->httpHeaders[$name] ?? null;

        if ($tryParse === true && $header === null) {
            if (str_starts_with($name, 'http-')) {
                // Trying to find client header variant which was not found,
                // searching for header variant without http- prefix.
                $header = $this->httpHeaders[str_replace(search: 'http-', replace: '', subject: $name)] ?? null;
            } else {
                // Trying to find server variant which was not found, searching
                // for client variant with http- prefix.
                $header = $this->httpHeaders['http-' . $name] ?? null;
            }
        }

        if ($header === null) {
            return $defaultValue;
        }

        return is_string($header) || is_numeric($header) ? (string) $header : $defaultValue;
    }

    /**
     * Will try to find first header from list of headers.
     *
     * @param array $headers
     * @param mixed|null $defaultValue
     * @return mixed|null
     */
    public function getFirstHeader(array $headers, mixed $defaultValue = null): mixed
    {
        foreach ($headers as $header) {
            $header = $this->getHttpHeader(name: $header);
            if ($header !== null) {
                return $header;
            }
        }

        return $defaultValue;
    }

    /**
     * Gets content type which request has been made.
     *
     * @return string|null
     */
    public function getContentType(): ?string
    {
        return $this->contentType !== '' ? $this->contentType : null;
    }

    /**
     * Set request content-type
     * @param string $contentType
     * @return $this
     */
    protected function withContentType(string $contentType): self
    {
        if (strpos($contentType, ';') > 0) {
            $this->contentType = strtolower(
                string: substr(
                    string: $contentType,
                    offset: 0,
                    length: strpos(haystack: $contentType, needle: ';')
                )
            );
        } else {
            $this->contentType = strtolower(string: $contentType);
        }

        return $this;
    }

    /**
     * Get input class
     */
    public function handler(): Handler
    {
        return $this->inputHandler;
    }

    /**
     * Is format accepted
     */
    public function isFormatAccepted(string $format): bool
    {
        return $this->getServer(name: 'http-accept') !== null
        && stripos(haystack: $this->getServer(name: 'http-accept'), needle: $format) !== false;
    }

    /**
     * Returns true if the request is made through Ajax
     *
     * @return bool
     */
    public function isAjax(): bool
    {
        return (strtolower(string: (string)$this->getHttpHeader(name: 'http-x-requested-with')) === 'xmlhttprequest');
    }

    /**
     * Gets auth info accepted by the browser/client.
     *
     * @return array|null
     */
    public function getBasicAuth(): ?array
    {
        if (! $this->hasServer(name: 'php-auth-user') || ! $this->hasServer(name: 'php-auth-pw')) {
            return null;
        }

        return [
            'username' => $this->getServer(name: 'php-auth-user'),
            'password' => $this->getServer(name: 'php-auth-pw'),
        ];
    }

    /**
     * Gets auth info accepted by the browser/client.
     *
     * @return array
     */
    public function getDigestAuth(): array
    {
        $auth = [];

        if ($digest = $this->getServer(name: 'php-auth-digest')) {
            $matches = [];

            if (! preg_match_all("#(\\w+)=(['\"]?)([^'\" ,]+)\\2#", $digest, $matches, 2)) {
                return $auth;
            }

            foreach ($matches as $match) {
                $auth[$match[1]] = $match[3];
            }
        }

        return $auth;
    }

    /**
     * Gets most possible client IPv4 Address.
     *
     * @param bool $trustForwardedHeader
     * @return bool|string
     */
    public function getClientAddress(bool $trustForwardedHeader = false): bool|string
    {
        $address = null;

        /**
         * Proxies uses this IP.
         */
        if ($trustForwardedHeader) {
            $address = $this->getServer(name: 'http-x-forwarded-for');

            if ($address === null) {
                $address = $this->getServer(name: 'http-client-ip');
            }
        }

        if ($address === null) {
            $address = $this->getServer(name: 'remote-addr');
        }

        if (! is_string(value: $address)) {
            return false;
        }

        if (str_contains($address, ',')) {
            /**
             * The client address has multiples parts, only return the first
             * part.
             */
            return trim(explode(separator: ',', string: $address)[0]);
        }

        return trim($address);
    }

    /**
     * Gets active server address IP.
     */
    public function getServerAddress(): string
    {
        $serverAddr = $this->getServer(name: 'server-addr');

        if (null === $serverAddr) {
            return gethostbyname(hostname: 'localhost');
        }

        return $serverAddr;
    }

    /**
     * Gets active server name.
     */
    public function getServerName(): string
    {
        $serverName = $this->getServer(name: 'server-name');

        if (null === $serverName) {
            return 'localhost';
        }

        return $serverName;
    }

    /**
     * Gets HTTP schema (http/https).
     */
    public function getScheme(): string
    {
        $scheme = $this->getUri()->getScheme();

        return $scheme !== '' ? $scheme : ($this->isSecure() ? 'https' : 'http');
    }

    /**
     * Gets variable from $_SERVER super global.
     */
    public function getServer(string $name): ?string
    {
        $name = strtolower(str_replace('_', '-', $name));

        if (! array_key_exists($name, $this->httpHeaders)) {
            return null;
        }

        $serverValue = $this->httpHeaders[$name];
        if (! is_string($serverValue) && ! is_numeric($serverValue)) {
            return null;
        }

        return (string) $serverValue;
    }

    /**
     * Checks whether $_SERVER super global has certain index.
     */
    final public function hasServer(string $name): bool
    {
        $name = strtolower(str_replace('_', '-', $name));

        return array_key_exists($name, $this->httpHeaders);
    }

    /**
     * Returns true when request-method is type that could contain data in the page body.
     *
     * @return bool
     */
    public function isPostBack(): bool
    {
        return in_array(needle: strtolower($this->getMethod()), haystack: self::$requestTypesPost, strict: true);
    }

    /**
     * Get accept formats.
     *
     * @return array
     */
    public function getAcceptFormats(): array
    {
        $header = $this->getHttpHeader(name: 'http-accept');

        if ($header === null || $header === '') {
            return [];
        }

        return array_map(static fn (string $format): string => trim($format), explode(',', $header));
    }

    public function setUrl(Url $url): void
    {
        $this->url = $url;

        if ($this->isSecure() === true && ! $url->isSecure()) {
            $url = $url->withScheme(scheme: 'https');
        }

        $this->url = $url;
    }

    public function setHost(?string $host): void
    {
        $this->host = $host;
    }

    public function setMethod(string $method): void
    {
        $this->method = strtolower(string: $method);
    }

    /**
     * Get rewrite url.
     */
    public function getRewriteUrl(): ?string
    {
        return $this->rewriteUrl;
    }

    /**
     * Set rewrite url.
     *
     * @return $this
     */
    public function setRewriteUrl(string $rewriteUrl): self
    {
        $this->hasPendingRewrite = true;
        $this->rewriteUrl = rtrim(string: $rewriteUrl, characters: '/') . '/';

        return $this;
    }

    /**
     * Does this request use a given method?
     *
     * @param  string $method HTTP method.
     */
    public function isMethod(string $method): bool
    {
        return $this->getMethod() === strtoupper(string: $method);
    }

    /**
     * Checks whether HTTP method is DELETE.
     */
    public function isDelete(): bool
    {
        return $this->isMethod(method: 'delete');
    }

    /**
     * Checks whether HTTP method is GET.
     */
    public function isGet(): bool
    {
        return $this->isMethod(method: 'get');
    }

    /**
     * Checks whether HTTP method is HEAD.
     */
    public function isHead(): bool
    {
        return $this->isMethod(method: 'head');
    }

    /**
     * Checks whether HTTP method is OPTIONS.
     */
    public function isOptions(): bool
    {
        return $this->isMethod(method: 'options');
    }

    /**
     * Checks whether HTTP method is PATCH.
     */
    public function isPatch(): bool
    {
        return $this->isMethod(method: 'patch');
    }

    /**
     * Checks whether HTTP method is POST.
     */
    public function isPost(): bool
    {
        return $this->isMethod(method: 'post');
    }

    /**
     * Checks whether HTTP method is PUT.
     */
    public function isPut(): bool
    {
        return $this->isMethod(method: 'put');
    }

    /**
     * Checks whether HTTP method is CONNECT.
     */
    public function isConnect(): bool
    {
        return $this->isMethod(method: 'connect');
    }

    /**
     * Checks whether HTTP method is TRACE.
     */
    public function isTrace(): bool
    {
        return $this->isMethod(method: 'trace');
    }

    /**
     * Checks if a method is a valid HTTP method.
     */
    public function isValidHttpMethod(string $method): bool
    {
        return match (strtoupper(string: $method)) {
            'GET','POST','PUT','DELETE','HEAD','OPTIONS',
            'PATCH','TRACE','CONNECT' => true,
            default => false,
        };
    }

    protected function getServerArray(): array
    {
        return $_SERVER;
    }

    public function __isset(string $name)
    {
        return array_key_exists(key: $name, array: $this->data) === true;
    }

    public function __set(string $name, ?string $value = null)
    {
        $this->data[$name] = $value;
    }

    public function __get(string $name)
    {
        return $this->data[$name] ?? null;
    }
}
