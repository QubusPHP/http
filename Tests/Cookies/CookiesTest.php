<?php

/**
 * Qubus\Http
 *
 * @link       https://github.com/QubusPHP/http
 * @copyright  2020 Joshua Parker <josh@joshuaparker.blog>
 * @copyright  2015 Beau Simensen <beau@dflydev.com>
 * @license    https://opensource.org/licenses/mit-license.php MIT License
 *
 * @since      1.0.0
 */

declare(strict_types=1);

namespace Qubus\Tests\Http\Cookies;

use PHPUnit\Framework\TestCase;
use Qubus\Http\Cookies\CookieCollection;
use Qubus\Http\Cookies\Cookies;
use Qubus\Http\Cookies\CookiesRequest;
use Qubus\Http\Cookies\CookiesResponse;
use Qubus\Http\Cookies\SetCookieCollection;
use Qubus\Http\Cookies\SetCookies;
use Qubus\Tests\Http\Cookies\CookieRequestTesting;
use Qubus\Tests\Http\Cookies\CookieResponseTesting;

use function str_rot13;

class CookiesTest extends TestCase
{
    /**
     * @test
     */
    public function testEncryptsAndDecryptsCookies(): void
    {
        // Simulate a request coming in with several cookies.
        $request = (new CookieRequestTesting())
            ->withHeader(Cookies::COOKIE_HEADER, 'theme=light; sessionToken=RAPELCGRQ; hello=world');

        // "Before" Middleware Example
        //
        // Get our token from an encrypted cookie value, "decrypt" it, and replace the cookie on the request.
        // From here on out, any part of the system that gets our token will be able to see the contents
        // in plaintext.
        $request = CookiesRequest::modify($request, 'sessionToken', function (CookieCollection $cookie): CookieCollection {
            return $cookie->withValue(str_rot13($cookie->getValue()));
        });

        // Even though the sessionToken initially comes in "encrypted", at this point (and any point in
        // the future) the sessionToken cookie will be available in plaintext.
        self::assertEquals(
            'theme=light; sessionToken=ENCRYPTED; hello=world',
            $request->getHeaderLine(Cookies::COOKIE_HEADER)
        );

        // Simulate a response going out.
        $response = new CookieResponseTesting();

        // Various parts of the system will add set cookies to the response. In this case, we are
        // going to show that the rest of the system interacts with the session token using
        // plaintext.
        $response = $response
            ->withAddedHeader(SetCookies::SET_COOKIE_HEADER, SetCookieCollection::create('theme', 'light'))
            ->withAddedHeader(SetCookies::SET_COOKIE_HEADER, SetCookieCollection::create('sessionToken', 'ENCRYPTED'))
            ->withAddedHeader(SetCookies::SET_COOKIE_HEADER, SetCookieCollection::create('hello', 'world'));

        // "After" Middleware Example
        //
        // Get our token from an unencrypted set cookie value, "encrypt" it, and replace the cookie on the response.
        // From here on out, any part of the system that gets our token will only be able to see the encrypted
        // value.
        $response = CookiesResponse::modify(
            $response,
            'sessionToken',
            function (SetCookieCollection $setCookie): SetCookieCollection {
                return $setCookie->withValue(str_rot13($setCookie->getValue()));
            }
        );

        // Even though the sessionToken intiially went out "decrypted", at this point (and at any point
        // in the future) the sessionToken cookie will remain "encrypted."
        self::assertEquals(
            ['theme=light', 'sessionToken=RAPELCGRQ', 'hello=world'],
            $response->getHeader(SetCookies::SET_COOKIE_HEADER)
        );
    }
}
