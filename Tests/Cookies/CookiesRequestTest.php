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
use Qubus\Tests\Http\Cookies\CookieRequestTesting;

use function strtoupper;

class CookiesRequestTest extends TestCase
{
    /**
     * @test
     */
    public function testGetsCookies(): void
    {
        $request = (new CookieRequestTesting())
            ->withHeader(Cookies::COOKIE_HEADER, 'theme=light; sessionToken=RAPELCGRQ; hello=world');

        self::assertEquals(
            'RAPELCGRQ',
            CookiesRequest::get($request, 'sessionToken')->getValue()
        );
    }

    /**
     * @test
     */
    public function testSetsCookies(): void
    {
        $request = (new CookieRequestTesting())
            ->withHeader(Cookies::COOKIE_HEADER, 'theme=light; sessionToken=RAPELCGRQ; hello=world');

        $request = CookiesRequest::set($request, CookieCollection::create('hello', 'WORLD!'));

        self::assertEquals(
            'theme=light; sessionToken=RAPELCGRQ; hello=WORLD%21',
            $request->getHeaderLine('Cookie')
        );
    }

    /**
     * @test
     */
    public function testModifiesCookies(): void
    {
        $request = (new CookieRequestTesting())
            ->withHeader(Cookies::COOKIE_HEADER, 'theme=light; sessionToken=RAPELCGRQ; hello=world');

        $request = CookiesRequest::modify($request, 'hello', function (CookieCollection $cookie) {
            return $cookie->withValue(strtoupper($cookie->getName()));
        });

        self::assertEquals(
            'theme=light; sessionToken=RAPELCGRQ; hello=HELLO',
            $request->getHeaderLine('Cookie')
        );
    }

    /**
     * @test
     */
    public function testRemovesCookies(): void
    {
        $request = (new CookieRequestTesting())
            ->withHeader(Cookies::COOKIE_HEADER, 'theme=light; sessionToken=RAPELCGRQ; hello=world');

        $request = CookiesRequest::remove($request, 'sessionToken');

        self::assertEquals(
            'theme=light; hello=world',
            $request->getHeaderLine('Cookie')
        );
    }
}
