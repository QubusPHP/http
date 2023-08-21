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

use InvalidArgumentException;
use Laminas\Diactoros\Request as BaseRequest;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;
use Qubus\Http\Exception\MalformedUrlException;
use Qubus\Http\Input\Handler;

use function array_key_exists;
use function explode;
use function gethostbyname;
use function is_array;
use function is_string;
use function preg_match_all;
use function rtrim;
use function str_replace;
use function stripos;
use function strpos;
use function strtolower;
use function strtoupper;

final class Request extends BaseRequest implements RequestInterface
{
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
            $this->httpHeaders[strtolower($key)]                        = $value;
            $this->httpHeaders[strtolower(str_replace('_', '-', $key))] = $value;
        }
        $this->setHost($this->getHttpHeader('http-host'));
        if (null === $uri) {
            // Check if special IIS header exist, otherwise use default.
            if (! empty($this->getHttpHeader('unencoded-url'))) {
                $uri = $this->getScheme() . '://' . $this->getHost() . $this->getHttpHeader('unencoded-url');
            }
            if (empty($this->getHttpHeader('unencoded-url'))) {
                $uri = $this->getScheme() . '://' . $this->getHost() . $this->getHttpHeader('request-uri');
            }
        }

        $this->setUrl(new Url($uri));

        $this->method       = $this->getHttpHeader('request-method');
        $this->inputHandler = new Handler($this);
        $this->method       = $this->inputHandler->value('_method', $this->getHttpHeader('request-method'));

        if (null === $method) {
            $method = $this->getMethod();
        }
        if ('' === $headers) {
            $headers = $this->getHttpHeaders();
        }

        parent::__construct($uri, $method, $body, $headers);
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

    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * Get http basic auth user.
     */
    public function getAuthUser(): ?string
    {
        return $this->getServer('php-auth-user');
    }

    /**
     * Get http basic auth password.
     */
    public function getAuthPassword(): ?string
    {
        return $this->getServer('php-auth-pw');
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
     * Get referer.
     */
    public function getReferer(): ?string
    {
        return $this->getServer('http-referer');
    }

    /**
     * Get user agent.
     */
    public function getUserAgent(): ?string
    {
        return $this->getServer('http-user-agent');
    }

    /**
     * Get header value by name
     */
    public function getHttpHeader(string $name, ?string $defaultValue = null): ?string
    {
        $name = strtolower(str_replace('_', '-', $name));
        return $this->httpHeaders[$name] ?? $defaultValue;
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
        return $this->getServer('http-accept') !== null && stripos($this->getServer('http-accept'), $format) !== false;
    }

    /**
     * Gets auth info accepted by the browser/client.
     *
     * @return array|null
     */
    public function getBasicAuth(): ?array
    {
        if (! $this->hasServer('php-auth-user') || ! $this->hasServer('php-auth-pw')) {
            return null;
        }

        return [
            'username' => $this->getServer('php-auth-user'),
            'password' => $this->getServer('php-auth-pw'),
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

        if ($digest = $this->getServer('php-auth-digest')) {
            $matches = [];

            if (! preg_match_all("#(\\w+)=(['\"]?)([^'\" ,]+)\\2#", $digest, $matches, 2)) {
                return $auth;
            }

            if (is_array($matches)) {
                foreach ($matches as $match) {
                    $auth[$match[1]] = $match[3];
                }
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
            $address = $this->getServer('http-x-forwarded-for');

            if ($address === null) {
                $address = $this->getServer('http-client-ip');
            }
        }

        if ($address === null) {
            $address = $this->getServer('remote-addr');
        }

        if (! is_string($address)) {
            return false;
        }

        if (strpos($address, ',')) {
            /**
             * The client address has multiples parts, only return the first
             * part.
             */
            return explode(',', $address)[0];
        }

        return $address;
    }

    /**
     * Gets content type which request has been made.
     */
    public function getContentType(): ?string
    {
        if (! $contentType = $this->getServer('content-type')) {
            return null;
        }

        return $contentType;
    }

    /**
     * Gets active server address IP.
     */
    public function getServerAddress(): string
    {
        $serverAddr = $this->getServer('server-addr');

        if (null === $serverAddr) {
            return gethostbyname('localhost');
        }

        return $serverAddr;
    }

    /**
     * Gets active server name.
     */
    public function getServerName(): string
    {
        $serverName = $this->getServer('server-name');

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
        return $this->isSecure() ? 'https' : 'http';
    }

    /**
     * Gets variable from $_SERVER super global.
     */
    public function getServer(string $name): ?string
    {
        if (! $serverValue = $this->httpHeaders[$name]) {
            return null;
        }

        return $serverValue;
    }

    /**
     * Checks whether $_SERVER super global has certain index.
     */
    final public function hasServer(string $name): string
    {
        return $this->httpHeaders[$name];
    }

    /**
     * Checks whether request has been made using ajax.
     */
    public function isAjax(): bool
    {
        if (
            array_key_exists('HTTP_X_REQUESTED_WITH', $this->getServerArray()) &&
            $this->getServer('http-x-requested-with') === 'XMLHttpRequest'
        ) {
            return true;
        }

        return false;
    }

    /**
     * Get accept formats.
     *
     * @return array
     */
    public function getAcceptFormats(): array
    {
        return explode(',', $this->getServer('http-accept'));
    }

    public function setUrl(Url $url): void
    {
        $this->url = $url;
        if ($this->url->getHost() === null) {
            $this->url->withHost((string) $this->getHost());
        }
    }

    public function setHost(?string $host): void
    {
        $this->host = $host;
    }

    public function setMethod(string $method): void
    {
        $this->method = strtolower($method);
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
     * @return static
     */
    public function setRewriteUrl(string $rewriteUrl): self
    {
        $this->hasPendingRewrite = true;
        $this->rewriteUrl = rtrim($rewriteUrl, '/') . '/';
        return $this;
    }

    /**
     * Does this request use a given method?
     *
     * @param  string $method HTTP method.
     */
    public function isMethod(string $method): bool
    {
        return $this->getMethod() === strtoupper($method);
    }

    /**
     * Checks whether HTTP method is DELETE.
     */
    public function isDelete(): bool
    {
        return $this->isMethod('delete');
    }

    /**
     * Checks whether HTTP method is GET.
     */
    public function isGet(): bool
    {
        return $this->isMethod('get');
    }

    /**
     * Checks whether HTTP method is HEAD.
     */
    public function isHead(): bool
    {
        return $this->isMethod('head');
    }

    /**
     * Checks whether HTTP method is OPTIONS.
     */
    public function isOptions(): bool
    {
        return $this->isMethod('options');
    }

    /**
     * Checks whether HTTP method is PATCH.
     */
    public function isPatch(): bool
    {
        return $this->isMethod('patch');
    }

    /**
     * Checks whether HTTP method is POST.
     */
    public function isPost(): bool
    {
        return $this->isMethod('post');
    }

    /**
     * Checks whether HTTP method is PUT.
     */
    public function isPut(): bool
    {
        return $this->isMethod('put');
    }

    /**
     * Checks whether HTTP method is CONNECT.
     */
    public function isConnect(): bool
    {
        return $this->isMethod('connect');
    }

    /**
     * Checks whether HTTP method is TRACE.
     */
    public function isTrace(): bool
    {
        return $this->isMethod('trace');
    }

    /**
     * Checks whether request has been made using a secure layer.
     */
    public function isSecure(): bool
    {
        if (
            (array_key_exists('HTTPS', $this->getServerArray()) &&
            (! empty($this->getServer('https')) && $this->getServer('https') !== 'off')) ||
            $this->getServer('server-port') === '443'
        ) {
            return true;
        }

        return false;
    }

    /**
     * Checks if a method is a valid HTTP method.
     */
    public function isValidHttpMethod(string $method): bool
    {
        return match (strtoupper($method)) {
            'GET','POST','PUT','DELETE','HEAD','OPTIONS',
            'PATCH','TRACE','CONNECT' => true,
            default => false,
        };
    }

    protected function getServerArray(): array
    {
        return $_SERVER ? $_SERVER : [];
    }

    public function __isset(string $name)
    {
        return array_key_exists($name, $this->data) === true;
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
