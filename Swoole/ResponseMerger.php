<?php

declare(strict_types=1);

namespace Qubus\Http\Swoole;

use Psr\Http\Message\ResponseInterface;
use Qubus\Exception\Data\TypeException;
use Qubus\Http\Cookies\SameSite;
use Qubus\Http\Cookies\SetCookies;
use Swoole\Http\Response;

use function explode;
use function feof;
use function fread;
use function implode;
use function is_resource;
use function is_string;
use function pclose;
use function strtolower;

class ResponseMerger
{
    public const int FSTAT_MODE_S_IFIFO = 0010000;
    public const int BUFFER_SIZE = 8192;

    protected const string FILES_STREAM_TYPE = 'STDIO';
    protected const string FILES_WRAPPER_TYPE = 'plainfile';

    public function toSwoole(ResponseInterface $psrResponse, Response $swooleResponse): Response
    {
        $swooleResponse->status($psrResponse->getStatusCode());
        $this->copyHeaders($psrResponse, $swooleResponse);
        $this->copyBody($psrResponse, $swooleResponse);

        return $swooleResponse;
    }

    /**
     * @throws TypeException
     */
    private function copyHeaders($psrResponse, $swooleResponse): void
    {
        if (empty($psrResponse->getHeaders())) {
            return;
        }

        $this->setCookies($swooleResponse, $psrResponse);

        $psrResponse = $psrResponse->withoutHeader('Set-Cookie');

        foreach ($psrResponse->getHeaders() as $key => $headerArray) {
            $swooleResponse->header($key, implode('; ', $headerArray));
        }
    }

    /**
     * @throws TypeException
     */
    private function setCookies($swooleResponse, $psrResponse): void
    {
        if (!$psrResponse->hasHeader('Set-Cookie')) {
            return;
        }

        $setCookies = SetCookies::fromSetCookieStrings($psrResponse->getHeader('Set-Cookie'));
        foreach ($setCookies->getAll() as $setCookie) {
            $swooleResponse->cookie(
                $setCookie->getName(),
                $setCookie->getValue() ?? '',
                $setCookie->getExpires(),
                $setCookie->getPath() ?? '/',
                $setCookie->getDomain() ?? '',
                $setCookie->getSecure(),
                $setCookie->getHttpOnly(),
                $this->getSameSiteModifier($setCookie)
            );
        }
    }

    private function getSameSiteModifier($setCookie): string
    {
        $sameSite = $setCookie->getSameSite() ?? SameSite::lax();

        return explode('=', strtolower((string) $sameSite->asString()))[1];
    }

    private function copyBody($psrResponse, $swooleResponse): void
    {
        if ($this->isFileStreamInBody($psrResponse)) {
            $swooleResponse->sendfile($psrResponse->getBody()->getMetadata('uri'));
            return;
        }

        if ($psrResponse->getBody()->getSize() == 0) {
            $this->copyBodyIfIsAPipe($psrResponse, $swooleResponse);
            return;
        }

        if ($psrResponse->getBody()->isSeekable()) {
            $psrResponse->getBody()->rewind();
        }

        $swooleResponse->write($psrResponse->getBody()->getContents());
    }

    private function copyBodyIfIsAPipe($psrResponse, $swooleResponse): void
    {
        $resource = $psrResponse->getBody()->detach();

        if (!is_resource($resource)) {
            return;
        }

        if ($this->isPipe($resource)) {
            while (!feof($resource)) {
                $buff = fread($resource, self::BUFFER_SIZE);
                !empty($buff) && $swooleResponse->write($buff);
            }
            pclose($resource);
        }
    }

    private function isPipe($resource): bool
    {
        $stat = fstat($resource);
        return ($stat['mode'] & self::FSTAT_MODE_S_IFIFO) === self::FSTAT_MODE_S_IFIFO;
    }

    private function isFileStreamInBody(ResponseInterface $psrResponse): bool
    {
        $streamType = explode('/', (string) $psrResponse->getBody()->getMetadata('stream_type'))[0] ?? '';
        $wrapperType = explode('/', (string) $psrResponse->getBody()->getMetadata('wrapper_type'))[0] ?? '';

        return
        $streamType === static::FILES_STREAM_TYPE &&
        $wrapperType === static::FILES_WRAPPER_TYPE &&
        is_string($psrResponse->getBody()->getMetadata('uri'));
    }
}
