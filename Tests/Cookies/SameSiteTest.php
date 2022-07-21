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
use Qubus\Exception\Data\TypeException;
use Qubus\Http\Cookies\SameSite;

/**
 * @covers \Qubus\Http\Cookies\SameSite
 */

final class SameSiteTest extends TestCase
{
    /** @test */
    public function testCanBeaStrictSameSiteModifier(): void
    {
        $strict = SameSite::strict();

        self::assertInstanceOf(SameSite::class, $strict);
        self::assertSame('SameSite=Strict', $strict->asString());
        self::assertEquals(SameSite::strict(), $strict, 'Multiple instances are equivalent');
    }

    /** @test */
    public function testCanBeaLaxSameSiteModifier(): void
    {
        $lax = SameSite::lax();

        self::assertInstanceOf(SameSite::class, $lax);
        self::assertSame('SameSite=Lax', $lax->asString());
        self::assertEquals(SameSite::lax(), $lax, 'Multiple instances are equivalent');
    }

    /** @test */
    public function testCanBeaNoneSameSiteModifier(): void
    {
        $none = SameSite::none();

        self::assertInstanceOf(SameSite::class, $none);
        self::assertSame('SameSite=None', $none->asString());
        self::assertEquals(SameSite::none(), $none, 'Multiple instances are equivalent');
    }

    /** @test */
    public function testLaxStrictAndNoneAreDifferent(): void
    {
        self::assertNotEquals(SameSite::lax(), SameSite::strict());
        self::assertNotEquals(SameSite::lax(), SameSite::none());
        self::assertNotEquals(SameSite::strict(), SameSite::none());
    }

    /**
     * @test
     * @expectedException \Qubus\Exception\Data\TypeException
     */
    public function testCanBeBuiltFromaString(): void
    {
        $this->expectException(TypeException::class);

        self::assertEquals(SameSite::strict(), SameSite::fromString('Strict'));
        self::assertEquals(SameSite::strict(), SameSite::fromString('strict'));
        self::assertEquals(SameSite::strict(), SameSite::fromString('stRiCt'));
        self::assertEquals(SameSite::lax(), SameSite::fromString('Lax'));
        self::assertEquals(SameSite::lax(), SameSite::fromString('lax'));
        self::assertEquals(SameSite::lax(), SameSite::fromString('lAx'));
        self::assertEquals(SameSite::none(), SameSite::fromString('None'));
        self::assertEquals(SameSite::none(), SameSite::fromString('none'));
        self::assertEquals(SameSite::none(), SameSite::fromString('nOnE'));

        SameSite::fromString('foo');
    }
}
