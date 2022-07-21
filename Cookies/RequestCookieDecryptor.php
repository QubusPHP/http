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
    public function __construct(
        public readonly Decryptor $decryptor,
        public readonly Validation $validation,
    ) {
    }

    private static function resolveCookieNames($cookieNames): array
    {
        return is_array($cookieNames) ? $cookieNames : [$cookieNames];
    }

    private static function hasNoCookieNames(array $cookieNames): bool
    {
        return count($cookieNames) < 1;
    }

    public function decrypt(RequestInterface $request, $cookieNames): RequestInterface
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

    private function decryptCookie(Cookies $cookies, $cookieName): Cookies
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
