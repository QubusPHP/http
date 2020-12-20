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

namespace Qubus\Http\Cookies;

use Psr\Http\Message\RequestInterface;
use Qubus\Http\Cookies\Cookies;
use Qubus\Http\Cookies\Encryption\Decryptor;
use Qubus\Http\Cookies\Validation\Validation;

use function base64_decode;
use function count;
use function is_array;

class RequestCookieDecryptor
{
    private Decryptor $decryptor;

    private Validation $validation;

    public function __construct(Decryptor $decryptor, Validation $validaton)
    {
        $this->decryptor = $decryptor;
        $this->validation = $validaton;
    }

    private static function resolveCookieNames($cookieNames)
    {
        return is_array($cookieNames) ? $cookieNames : [(string) $cookieNames];
    }

    private static function hasNoCookieNames(array $cookieNames)
    {
        return count($cookieNames) < 1;
    }

    public function decrypt(RequestInterface $request, $cookieNames)
    {
        $cookieNames = self::resolveCookieNames($cookieNames);

        if (self::hasNoCookieNames($cookieNames)) {
            return $request;
        }

        $cookies = Cookies::fromRequest($request);

        foreach ($cookieNames as $cookieName) {
            $cookies = $this->decryptCookie($cookies, $cookieName);
        }

        return $cookies->renderIntoCookieHeader($request);
    }

    private function decryptCookie(Cookies $cookies, $cookieName)
    {
        if (! $cookies->has($cookieName)) {
            return $cookies;
        }

        $cookie = $cookies->get($cookieName);
        $encodedValue = $cookie->getValue();
        $signedValue = base64_decode($encodedValue);
        $encryptedValue = $this->validation->extract($signedValue);
        $decryptedValue = $this->decryptor->decrypt($encryptedValue);
        $decryptedCookie = $cookie->withValue($decryptedValue);

        return $cookies->with($decryptedCookie);
    }
}
