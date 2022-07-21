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

namespace Qubus\Http\Cookies;

use Psr\Http\Message\ResponseInterface;
use Qubus\Http\Cookies\Encryption\Encryptor;
use Qubus\Http\Cookies\SetCookies;
use Qubus\Http\Cookies\Validation\Validation;

use function base64_encode;
use function count;
use function is_array;

class ResponseCookieEncryptor
{
    public function __construct(
        public readonly Encryptor $encryptor,
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

    public function encrypt(ResponseInterface $response, $cookieNames): ResponseInterface
    {
        $cookieNames = self::resolveCookieNames($cookieNames);

        if (self::hasNoCookieNames($cookieNames)) {
            return $response;
        }

        $setCookies = SetCookies::fromResponse($response);

        foreach ($cookieNames as $cookieName) {
            $setCookies = $this->encryptCookie($setCookies, $cookieName);
        }

        return $setCookies->renderIntoSetCookieHeader($response);
    }

    private function encryptCookie(SetCookies $setCookies, $cookieName): SetCookies
    {
        if (! $setCookies->has($cookieName)) {
            return $setCookies;
        }

        $cookie = $setCookies->get($cookieName);
        $decryptedValue = $cookie->getValue();
        $encryptedValue = $this->encryptor->encrypt($decryptedValue);
        $signedValue = $this->validation->sign($encryptedValue);
        $encodedValue = base64_encode($signedValue);
        $encryptedCookie = $cookie->withValue($encodedValue);

        return $setCookies->with($encryptedCookie);
    }
}
