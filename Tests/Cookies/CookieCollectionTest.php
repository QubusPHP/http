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
use Qubus\Http\Cookies\CookieCollection;
use PHPUnit\Framework\Assert;

class CookieCollectionTest extends TestCase
{
    /**
     * @test
     * @dataProvider provideParsesOneFromCookieStringData
     */
    public function testParsesOneFromCookieString(
        string $cookieString,
        string $expectedName,
        ?string $expectedValue
    ) : void {
        $cookie = CookieCollection::oneFromCookiePair($cookieString);

        $this->assertCookieNameAndValue($cookie, $expectedName, $expectedValue);
    }

    /**
     * @param string[] $expectedNameValuePairs
     *
     * @test
     * @dataProvider provideParsesListFromCookieString
     */
    public function testParsesListFromCookieString(string $cookieString, array $expectedNameValuePairs): void
    {
        $cookies = CookieCollection::listFromCookieString($cookieString);

        Assert::assertCount(count($expectedNameValuePairs), $cookies);

        for ($i = 0; $i < count($cookies); $i++) {
            $cookie                              = $cookies[$i];
            list($expectedName, $expectedValue) = $expectedNameValuePairs[$i];

            $this->assertCookieNameAndValue($cookie, $expectedName, $expectedValue);
        }
    }

    private function assertCookieNameAndValue(
        CookieCollection $cookie,
        string $expectedName,
        ?string $expectedValue
    ): void {
        Assert::assertEquals($expectedName, $cookie->getName());
        Assert::assertEquals($expectedValue, $cookie->getValue());
    }

    /** @return string[][] */
    public function provideParsesOneFromCookieStringData(): array
    {
        return [
            ['someCookie=something', 'someCookie', 'something'],
            ['hello%3Dworld=how%22are%27you', 'hello=world', 'how"are\'you'],
            ['empty=', 'empty', ''],
        ];
    }

    /** @return string[][]|string[][][][] */
    public function provideParsesListFromCookieString(): array
    {
        return [
            [
                'theme=light; sessionToken=abc123',
                [
                    ['theme', 'light'],
                    ['sessionToken', 'abc123'],
                ],
            ],

            [
                'theme=light; sessionToken=abc123;',
                [
                    ['theme', 'light'],
                    ['sessionToken', 'abc123'],
                ],
            ],
        ];
    }
}
