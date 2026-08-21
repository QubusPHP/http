<?php

declare(strict_types=1);

namespace Qubus\Http\Cookies\Middleware;

use Defuse\Crypto\Crypto;
use Defuse\Crypto\Exception\EnvironmentIsBrokenException;
use Defuse\Crypto\Key;
use Exception;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Qubus\Exception\Data\TypeException;
use Qubus\Http\Cookies\CookieCollection;
use Qubus\Http\Cookies\Cookies;
use Qubus\Http\Cookies\CookiesRequest;
use Qubus\Http\Cookies\CookiesResponse;
use Qubus\Http\Cookies\SetCookieCollection;
use Qubus\Http\Cookies\SetCookies;
use RuntimeException;

use function in_array;

class EncryptCookiesMiddleware implements MiddlewareInterface
{
    /**
     * A list of cookie names not to encrypt/decrypt.
     *
     * @var array<int, string>
     */
    protected array $bypass = [];


    /**
     * The key with which to encrypt cookies.
     *
     * @var Key|null
     */
    protected ?Key $key = null;

    /**
     * Create a new instance of the middleware
     *
     * @param Key $cryptoKey
     * @param array<int, string> $bypassCookieNames
     */
    public function __construct(Key $cryptoKey, array $bypassCookieNames = [])
    {
        $this->key    = $cryptoKey;
        $this->bypass = $bypassCookieNames;
    }

    /**
     * @inheritDoc
     * @throws TypeException
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        foreach (Cookies::fromRequest($request)->getAll() as $cookie) {
            if (! in_array($cookie->getName(), $this->bypass, true)) {
                $request = CookiesRequest::modify(
                    $request,
                    $cookie->getName(),
                    [$this, 'decrypt']
                );
            }
        }

        if (!$request instanceof ServerRequestInterface) {
            throw new RuntimeException(
                message: 'Modification of cookies on server request resulted in conversion to request.'
            );
        }

        $response = $handler->handle($request);

        foreach (SetCookies::fromResponse($response)->getAll() as $setCookie) {
            if (! in_array($setCookie->getName(), $this->bypass, true)) {
                $response = CookiesResponse::modify(
                    $response,
                    $setCookie->getName(),
                    [$this, 'encrypt']
                );
            }
        }

        return $response;
    }

    /**
     * @throws EnvironmentIsBrokenException
     */
    public function encrypt(SetCookieCollection $setCookie): SetCookieCollection
    {
        return $setCookie->withValue(Crypto::encrypt((string) $setCookie->getValue(), $this->key));
    }

    public function decrypt(CookieCollection $cookie): CookieCollection
    {
        if (!$cookie->getValue()) {
            return $cookie->withValue('');
        }

        try {
            return $cookie->withValue(Crypto::decrypt($cookie->getValue(), $this->key));
        } catch (Exception) {
            return $cookie->withValue('');
        }
    }
}
