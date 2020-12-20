<?php

/**
 * Qubus\Http
 *
 * @link       https://github.com/QubusPHP/http
 * @copyright  2020 Joshua Parker
 * @license    https://opensource.org/licenses/mit-license.php MIT License
 *
 * @since      1.0.0
 */

declare(strict_types=1);

namespace Qubus\Http;

use Laminas\Diactoros\Response as BaseResponse;
use Psr\Http\Message\ResponseInterface;

use function gmdate;
use function http_response_code;
use function sprintf;
use function strtotime;

class Response extends BaseResponse implements ResponseInterface
{
    public function __construct($body = 'php://memory', int $status = 200, array $headers = [])
    {
        parent::__construct($body, $status, $headers);
    }

    /**
     * Set the http status code
     *
     * @return static
     */
    public function httpCode(int $code): self
    {
        http_response_code($code);
        return $this;
    }

    /**
     * Redirect the response
     */
    public function redirect(string $url, ?int $httpCode = null): void
    {
        if ($httpCode !== null) {
            $this->httpCode($httpCode);
        }

        $this->header('location: ' . $url);
        exit(0);
    }

    public function refresh(): void
    {
        $this->redirect($this->request->getUrl()->getOriginalUrl());
    }

    /**
     * Add http authorisation
     *
     * @return static
     */
    public function auth(string $name = ''): self
    {
        $this->headers([
            'WWW-Authenticate: Basic realm="' . $name . '"',
            'HTTP/1.0 401 Unauthorized',
        ]);

        return $this;
    }

    public function cache(string $eTag, int $lastModifiedTime = 2592000): self
    {
        $this->headers([
            'Cache-Control: public',
            sprintf('Last-Modified: %s GMT', gmdate('D, d M Y H:i:s', $lastModifiedTime)),
            sprintf('Etag: %s', $eTag),
        ]);

        $httpModified    = $this->request->getHttpHeader('http-if-modified-since');
        $httpIfNoneMatch = $this->request->getHttpHeader('http-if-none-match');

        if (
            ($httpIfNoneMatch !== null && $httpIfNoneMatch === $eTag) ||
            ($httpModified !== null && strtotime($httpModified) === $lastModifiedTime)
        ) {
            $this->header('HTTP/1.1 304 Not Modified');
            exit(0);
        }

        return $this;
    }
}
