<?php

declare(strict_types=1);

namespace Qubus\Tests\Http\Core;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Qubus\Exception\Data\TypeException;
use Qubus\Http\Cookies\SetCookieCollection;
use Qubus\Http\Cookies\SameSite;
use Qubus\Http\Cookies\Validation\Validation;
use Qubus\Http\Session\SessionId;

final class SecurityRegressionTest extends TestCase
{
    public function testCookieAttributesCannotInjectHeaders(): void
    {
        $this->expectException(TypeException::class);

        SetCookieCollection::create('session')->withPath("/\r\nX-Injected: yes");
    }

    public function testCookieSignaturesRequireASecureKeyAndValidMessage(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Validation('');
    }

    public function testMalformedSignedCookiesAreRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Validation('secret')->extract('malformed');
    }

    public function testSessionIdInputLengthIsValidatedWithoutAssertions(): void
    {
        $this->expectException(InvalidArgumentException::class);

        SessionId::create('too-short');
    }

    public function testMaxAgeZeroIsRenderedForCookieDeletion(): void
    {
        $cookie = SetCookieCollection::create('session', '')->withMaxAge(0);

        self::assertStringContainsString('Max-Age=0', (string) $cookie);
    }

    public function testModernCookieSecurityRulesAndPartitionedAttribute(): void
    {
        $cookie = SetCookieCollection::create('__Host-session', 'value')
            ->withPath('/')
            ->withSecure()
            ->withSameSite(SameSite::none())
            ->withPartitioned();

        self::assertStringContainsString('SameSite=None', (string) $cookie);
        self::assertStringContainsString('Partitioned', (string) $cookie);
        self::assertTrue(SetCookieCollection::fromSetCookieString((string) $cookie)->getPartitioned());
    }

    public function testSameSiteNoneCannotBeEmittedWithoutSecure(): void
    {
        $this->expectException(TypeException::class);

        (string) SetCookieCollection::create('session')->withSameSite(SameSite::none());
    }
}
