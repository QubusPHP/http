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

namespace Qubus\Tests\Http\Cookies;

use PHPUnit\Framework\TestCase;
use Qubus\Http\Cookies\SameSite;
use Qubus\Exception\Data\TypeException;
use Qubus\Http\Cookies\SetCookieCollection;
use Qubus\ValueObjects\DateTime\DateTime;
use Carbon\CarbonImmutable;

class SetCookieCollectionTest extends TestCase
{
    /**
     * @test
     * @dataProvider provideParsesFromSetCookieStringData
     */
    public function testParsesFromSetCookieString(string $cookieString, SetCookieCollection $expectedSetCookie): void
    {
        $setCookie = SetCookieCollection::fromSetCookieString($cookieString);

        self::assertEquals($expectedSetCookie, $setCookie);
        self::assertEquals($cookieString, (string) $setCookie);
    }

    /** @return string[][]|SetCookie[][] */
    public function provideParsesFromSetCookieStringData(): array
    {
        return [
            [
                'someCookie=',
                SetCookieCollection::create('someCookie'),
            ],
            [
                'someCookie=someValue',
                SetCookieCollection::create('someCookie')
                    ->withValue('someValue'),
            ],
            [
                'LSID=DQAAAK%2FEaem_vYg; Path=/accounts; Expires=Wed, 13 Jan 2021 22:23:01 GMT; Secure; HttpOnly',
                SetCookieCollection::create('LSID')
                    ->withValue('DQAAAK/Eaem_vYg')
                    ->withPath('/accounts')
                    ->withExpires('Wed, 13 Jan 2021 22:23:01 GMT')
                    ->withSecure(true)
                    ->withHttpOnly(true),
            ],
            [
                'HSID=AYQEVn%2F.DKrdst; Domain=.foo.com; Path=/; Expires=Wed, 13 Jan 2021 22:23:01 GMT; HttpOnly',
                SetCookieCollection::create('HSID')
                    ->withValue('AYQEVn/.DKrdst')
                    ->withDomain('.foo.com')
                    ->withPath('/')
                    ->withExpires('Wed, 13 Jan 2021 22:23:01 GMT')
                    ->withHttpOnly(true),
            ],
            [
                'SSID=Ap4P%2F.GTEq; Domain=foo.com; Path=/; Expires=Wed, 13 Jan 2021 22:23:01 GMT; Secure; HttpOnly',
                SetCookieCollection::create('SSID')
                    ->withValue('Ap4P/.GTEq')
                    ->withDomain('foo.com')
                    ->withPath('/')
                    ->withExpires('Wed, 13 Jan 2021 22:23:01 GMT')
                    ->withSecure(true)
                    ->withHttpOnly(true),
            ],
            [
                'lu=Rg3vHJZnehYLjVg7qi3bZjzg; Domain=.example.com; Path=/; Expires=Tue, 15 Jan 2013 21:47:38 GMT; HttpOnly',
                SetCookieCollection::create('lu')
                    ->withValue('Rg3vHJZnehYLjVg7qi3bZjzg')
                    ->withExpires('Tue, 15-Jan-2013 21:47:38 GMT')
                    ->withPath('/')
                    ->withDomain('.example.com')
                    ->withHttpOnly(true),
            ],
            [
                'lu=Rg3vHJZnehYLjVg7qi3bZjzg; Domain=.example.com; Path=/; Max-Age=500; Secure; HttpOnly',
                SetCookieCollection::create('lu')
                    ->withValue('Rg3vHJZnehYLjVg7qi3bZjzg')
                    ->withMaxAge(500)
                    ->withPath('/')
                    ->withDomain('.example.com')
                    ->withSecure(true)
                    ->withHttpOnly(true),
            ],
            [
                'lu=Rg3vHJZnehYLjVg7qi3bZjzg; Domain=.example.com; Path=/; Expires=Tue, 15 Jan 2013 21:47:38 GMT; Max-Age=500; Secure; HttpOnly',
                SetCookieCollection::create('lu')
                    ->withValue('Rg3vHJZnehYLjVg7qi3bZjzg')
                    ->withExpires('Tue, 15-Jan-2013 21:47:38 GMT')
                    ->withMaxAge(500)
                    ->withPath('/')
                    ->withDomain('.example.com')
                    ->withSecure(true)
                    ->withHttpOnly(true),
            ],
            [
                'lu=Rg3vHJZnehYLjVg7qi3bZjzg; Domain=.example.com; Path=/; Expires=Tue, 15 Jan 2013 21:47:38 GMT; Max-Age=500; Secure; HttpOnly',
                SetCookieCollection::create('lu')
                    ->withValue('Rg3vHJZnehYLjVg7qi3bZjzg')
                    ->withExpires(1358286458)
                    ->withMaxAge(500)
                    ->withPath('/')
                    ->withDomain('.example.com')
                    ->withSecure(true)
                    ->withHttpOnly(true),
            ],
            [
                'lu=Rg3vHJZnehYLjVg7qi3bZjzg; Domain=.example.com; Path=/; Expires=Tue, 15 Jan 2013 21:47:38 GMT; Max-Age=500; Secure; HttpOnly',
                SetCookieCollection::create('lu')
                         ->withValue('Rg3vHJZnehYLjVg7qi3bZjzg')
                         ->withExpires(new \DateTime('Tue, 15-Jan-2013 21:47:38 GMT'))
                         ->withMaxAge(500)
                         ->withPath('/')
                         ->withDomain('.example.com')
                         ->withSecure(true)
                         ->withHttpOnly(true),
            ],
            [
                'lu=Rg3vHJZnehYLjVg7qi3bZjzg; Domain=.example.com; Path=/; Expires=Tue, 15 Jan 2013 21:47:38 GMT; Max-Age=500; Secure; HttpOnly; SameSite=Strict',
                SetCookieCollection::create('lu')
                         ->withValue('Rg3vHJZnehYLjVg7qi3bZjzg')
                         ->withExpires(new \DateTime('Tue, 15-Jan-2013 21:47:38 GMT'))
                         ->withMaxAge(500)
                         ->withPath('/')
                         ->withDomain('.example.com')
                         ->withSecure(true)
                         ->withHttpOnly(true)
                         ->withSameSite(SameSite::strict()),
            ],
            [
                'lu=Rg3vHJZnehYLjVg7qi3bZjzg; Domain=.example.com; Path=/; Expires=Tue, 15 Jan 2013 21:47:38 GMT; Max-Age=500; Secure; HttpOnly; SameSite=Lax',
                SetCookieCollection::create('lu')
                         ->withValue('Rg3vHJZnehYLjVg7qi3bZjzg')
                         ->withExpires(new \DateTime('Tue, 15-Jan-2013 21:47:38 GMT'))
                         ->withMaxAge(500)
                         ->withPath('/')
                         ->withDomain('.example.com')
                         ->withSecure(true)
                         ->withHttpOnly(true)
                         ->withSameSite(SameSite::lax()),
            ],
        ];
    }

    /**
     * @test
     */
    public function testExpiresCookies() : void
    {
        $setCookie = SetCookieCollection::createExpired('expire_immediately');

        self::assertLessThan(time(), $setCookie->getExpires());
    }

    /**
     * @test
     */
    public function testCreatesLongLivingCookies(): void
    {
        $setCookie = SetCookieCollection::createRememberedForever('remember_forever');

        $nativeDateTime = new CarbonImmutable('+4 years');
        $dateTimeFromNative = DateTime::fromNativeCarbonImmutable($nativeDateTime);
        $datetime = $dateTimeFromNative->toNativeCarbonImmutable();

        $fourYearsFromNow = $datetime->timestamp;
        self::assertGreaterThan($fourYearsFromNow, $setCookie->getExpires());
    }

    /** @test */
    public function testSameSiteModifierCanBeAddedandRemoved(): void
    {
        $setCookie = SetCookieCollection::create('foo', 'bar');

        self::assertNull($setCookie->getSameSite());
        self::assertSame('foo=bar', $setCookie->__toString());

        $setCookie = $setCookie->withSameSite(SameSite::strict());

        self::assertEquals(SameSite::strict(), $setCookie->getSameSite());
        self::assertSame('foo=bar; SameSite=Strict', $setCookie->__toString());

        $setCookie = $setCookie->withoutSameSite();
        self::assertNull($setCookie->getSameSite());
        self::assertSame('foo=bar', $setCookie->__toString());
    }

    /**
     * @test
     * @expectedException \Qubus\Exception\Data\TypeException
     */
    public function testInvalidExpiresFormatWillBeRejected(): void
    {
        $setCookie = SetCookieCollection::create('foo', 'bar');

        $this->expectExceptionMessage('Invalid expires "potato" provided.');

        $setCookie->withExpires('potato');
    }

    /**
     * @test
     * @expectedException \Qubus\Exception\Data\TypeException
     */
    public function testEmptyCookieIsRejected() : void
    {
        $this->expectExceptionMessage('The provided cookie string "" must have at least one attribute.');

        SetCookieCollection::fromSetCookieString('');
    }
}
