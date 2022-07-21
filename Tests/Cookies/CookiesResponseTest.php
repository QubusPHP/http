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
use Qubus\Http\Cookies\CookiesResponse;
use Qubus\Http\Cookies\SetCookieCollection;
use Qubus\Http\Cookies\SetCookies;
use Qubus\Tests\Http\Cookies\CookieResponseTesting;

use function strtoupper;

class CookiesResponseTest extends TestCase
{
    /**
     * @test
     */
    public function testGetsCookies(): void
    {
        $response = new CookieResponseTesting();

        $response = $response
            ->withAddedHeader(SetCookies::SET_COOKIE_HEADER, SetCookieCollection::create('theme', 'light'))
            ->withAddedHeader(SetCookies::SET_COOKIE_HEADER, SetCookieCollection::create('sessionToken', 'ENCRYPTED'))
            ->withAddedHeader(SetCookies::SET_COOKIE_HEADER, SetCookieCollection::create('hello', 'world'));

        self::assertEquals(
            'ENCRYPTED',
            CookiesResponse::get($response, 'sessionToken')->getValue()
        );
    }

    /**
     * @test
     */
    public function testSetsCookies(): void
    {
        $response = new CookieResponseTesting();

        $response = $response
            ->withAddedHeader(SetCookies::SET_COOKIE_HEADER, SetCookieCollection::create('theme', 'light'))
            ->withAddedHeader(SetCookies::SET_COOKIE_HEADER, SetCookieCollection::create('sessionToken', 'ENCRYPTED'))
            ->withAddedHeader(SetCookies::SET_COOKIE_HEADER, SetCookieCollection::create('hello', 'world'));

        $response = CookiesResponse::set($response, SetCookieCollection::create('hello', 'WORLD!'));

        self::assertEquals(
            'theme=light,sessionToken=ENCRYPTED,hello=WORLD%21',
            $response->getHeaderLine('Set-Cookie')
        );
    }

    /**
     * @test
     */
    public function testModifiesCookies(): void
    {
        $response = new CookieResponseTesting();

        $response = $response
            ->withAddedHeader(SetCookies::SET_COOKIE_HEADER, SetCookieCollection::create('theme', 'light'))
            ->withAddedHeader(SetCookies::SET_COOKIE_HEADER, SetCookieCollection::create('sessionToken', 'ENCRYPTED'))
            ->withAddedHeader(SetCookies::SET_COOKIE_HEADER, SetCookieCollection::create('hello', 'world'));

        $response = CookiesResponse::modify($response, 'hello', function (SetCookieCollection $setCookie) {
            return $setCookie->withValue(strtoupper($setCookie->getName()));
        });

        self::assertEquals(
            'theme=light,sessionToken=ENCRYPTED,hello=HELLO',
            $response->getHeaderLine('Set-Cookie')
        );
    }

    /**
     * @test
     */
    public function testRemovesCookies(): void
    {
        $response = new CookieResponseTesting();

        $response = $response
            ->withAddedHeader(SetCookies::SET_COOKIE_HEADER, SetCookieCollection::create('theme', 'light'))
            ->withAddedHeader(SetCookies::SET_COOKIE_HEADER, SetCookieCollection::create('sessionToken', 'ENCRYPTED'))
            ->withAddedHeader(SetCookies::SET_COOKIE_HEADER, SetCookieCollection::create('hello', 'world'));

        $response = CookiesResponse::remove($response, 'sessionToken');

        self::assertEquals(
            'theme=light,hello=world',
            $response->getHeaderLine('Set-Cookie')
        );
    }
}
