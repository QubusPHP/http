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
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Http\Message\ResponseInterface;
use Qubus\Http\Cookies\SetCookies;
use Qubus\Tests\Http\Cookies\CookieResponseTesting;
use Qubus\Http\Cookies\SetCookieCollection;

class SetCookiesTest extends TestCase
{
    use ProphecyTrait;
    
    public const INTERFACE_PSR_HTTP_MESSAGE_RESPONSE = ResponseInterface::class;

    /**
     * @param string[]    $setCookieStrings
     * @param SetCookieCollection[] $expectedSetCookies
     *
     * @test
     * @dataProvider provideSetCookieStringsAndExpectedSetCookiesData
     */
    public function testCreatesFromResponse(array $setCookieStrings, array $expectedSetCookies): void
    {
        /** @var ResponseInterface|ObjectProphecy $response */
        $response = $this->prophesize(static::INTERFACE_PSR_HTTP_MESSAGE_RESPONSE);
        $response->getHeader(SetCookies::SET_COOKIE_HEADER)->willReturn($setCookieStrings);

        $setCookies = SetCookies::fromResponse($response->reveal());

        self::assertEquals($expectedSetCookies, $setCookies->getAll());
    }

    /**
     * @param string[]              $setCookieStrings
     * @param SetCookieCollection[] $expectedSetCookies
     *
     * @test
     * @dataProvider provideSetCookieStringsAndExpectedSetCookiesData
     */
    public function testCreatesFromSetCookieStrings(array $setCookieStrings, array $expectedSetCookies): void
    {
        $setCookies = SetCookies::fromSetCookieStrings($setCookieStrings);

        self::assertEquals($expectedSetCookies, $setCookies->getAll());
    }

    /**
     * @param string[]              $setCookieStrings
     * @param SetCookieCollection[] $expectedSetCookies
     *
     * @test
     * @dataProvider provideSetCookieStringsAndExpectedSetCookiesData
     */
    public function testKnowsWhichSetCookiesAreAvailable(array $setCookieStrings, array $expectedSetCookies): void
    {
        $setCookies = SetCookies::fromSetCookieStrings($setCookieStrings);

        foreach ($expectedSetCookies as $expectedSetCookie) {
            self::assertTrue($setCookies->has($expectedSetCookie->getName()));
        }

        self::assertFalse($setCookies->has('i know this cookie does not exist'));
    }

    /**
     * @param string[] $setCookieStrings
     *
     * @test
     * @dataProvider provideGetsSetCookieByNameData
     */
    public function testGetsSetCookieByName(
        array $setCookieStrings,
        string $setCookieName,
        ?SetCookieCollection $expectedSetCookie = null
    ): void {
        $setCookies = SetCookies::fromSetCookieStrings($setCookieStrings);

        self::assertEquals($expectedSetCookie, $setCookies->get($setCookieName));
    }

    /**
     * @test
     */
    public function testRendersAddedAndRemovedSetCookiesHeader(): void
    {
        $setCookies = SetCookies::fromSetCookieStrings(['theme=light', 'sessionToken=abc123', 'hello=world'])
            ->with(SetCookieCollection::create('theme', 'blue'))
            ->without('sessionToken')
            ->with(SetCookieCollection::create('who', 'me'))
        ;

        $originalResponse = new CookieResponseTesting();
        $response         = $setCookies->renderIntoSetCookieHeader($originalResponse);

        self::assertNotEquals($response, $originalResponse);

        self::assertEquals(
            ['theme=blue', 'hello=world', 'who=me'],
            $response->getHeader(SetCookies::SET_COOKIE_HEADER)
        );
    }

    /**
     * @test
     */
    public function testGetsAndUpdatesSetCookieValueOnRequestt(): void
    {
        // Example of naive cookie encryption middleware.
        //
        // Shows how to access and manipulate cookies using PSR-7 Response
        // instances from outside the Response object itself.
        // Simulate a response coming in with several cookies.
        $response = (new CookieResponseTesting())
            ->withAddedHeader(SetCookies::SET_COOKIE_HEADER, 'theme=light')
            ->withAddedHeader(SetCookies::SET_COOKIE_HEADER, 'sessionToken=ENCRYPTED')
            ->withAddedHeader(SetCookies::SET_COOKIE_HEADER, 'hello=world');

        // Get our set cookies from the response.
        $setCookies = SetCookies::fromResponse($response);

        // Ask for the encrypted session token.
        $decryptedSessionToken = $setCookies->get('sessionToken');

        // Get the encrypted value from the cookie and decrypt it.
        $decryptedValue = $decryptedSessionToken->getValue();
        $encryptedValue = str_rot13($decryptedValue);

        // Create a new set cookie with the encrypted value.
        $encryptedSessionToken = $decryptedSessionToken->withValue($encryptedValue);

        // Include our encrypted session token with the rest of our cookies.
        $setCookies = $setCookies->with($encryptedSessionToken);

        // Render our cookies, along with the newly decrypted session token, into a response.
        $response = $setCookies->renderIntoSetCookieHeader($response);

        // From this point on, any response based on this one can get the encrypted version
        // of the session token.
        self::assertEquals(
            ['theme=light', 'sessionToken=RAPELCGRQ', 'hello=world'],
            $response->getHeader(SetCookies::SET_COOKIE_HEADER)
        );
    }

    /** @return string[][][]|SetCookie[][][] */
    public function provideSetCookieStringsAndExpectedSetCookiesData()
    {
        return [
            [
                [],
                [],
            ],
            [
                ['someCookie='],
                [
                    SetCookieCollection::create('someCookie'),
                ],
            ],
            [
                [
                    'someCookie=someValue',
                    'LSID=DQAAAK%2FEaem_vYg; Path=/accounts; Expires=Wed, 13 Jan 2021 22:23:01 GMT; Secure; HttpOnly',
                ],
                [
                    SetCookieCollection::create('someCookie', 'someValue'),
                    SetCookieCollection::create('LSID')
                        ->withValue('DQAAAK/Eaem_vYg')
                        ->withPath('/accounts')
                        ->withExpires('Wed, 13 Jan 2021 22:23:01 GMT')
                        ->withSecure(true)
                        ->withHttpOnly(true),
                ],
            ],
            [
                [
                    'a=AAA',
                    'b=BBB',
                    'c=CCC',
                ],
                [
                    SetCookieCollection::create('a', 'AAA'),
                    SetCookieCollection::create('b', 'BBB'),
                    SetCookieCollection::create('c', 'CCC'),
                ],
            ],
        ];
    }

    /** @return string[][]|string[][][]|SetCookie[][]|null[][] */
    public function provideGetsSetCookieByNameData() : array
    {
        return [
            [
                [
                    'a=AAA',
                    'b=BBB',
                    'c=CCC',
                ],
                'b',
                SetCookieCollection::create('b', 'BBB'),
            ],
            [
                [
                    'a=AAA',
                    'b=BBB',
                    'c=CCC',
                    'LSID=DQAAAK%2FEaem_vYg; Path=/accounts; Expires=Wed, 13 Jan 2021 22:23:01 GMT; Secure; HttpOnly',
                ],
                'LSID',
                SetCookieCollection::create('LSID')
                    ->withValue('DQAAAK/Eaem_vYg')
                    ->withPath('/accounts')
                    ->withExpires('Wed, 13 Jan 2021 22:23:01 GMT')
                    ->withSecure(true)
                    ->withHttpOnly(true),
            ],
            [
                [
                    'a=AAA',
                    'b=BBB',
                    'c=CCC',
                ],
                'LSID',
                null,
            ],
        ];
    }
}
