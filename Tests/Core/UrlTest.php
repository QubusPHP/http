<?php

declare(strict_types=1);

namespace Qubus\Tests\Http\Core;

use PHPUnit\Framework\TestCase;
use Qubus\Http\Exception\MalformedUrlException;
use Qubus\Http\Url;

final class UrlTest extends TestCase
{
    public function testPreservesEveryUriComponent(): void
    {
        $url = new Url('https://user:pass@example.com:8443/path?first=one#fragment');

        self::assertSame('https://user:pass@example.com:8443/path?first=one#fragment', (string) $url);
        self::assertSame('/path?first=one#fragment', $url->getRelativeUrl());
        self::assertSame('one', $url->getParam('first'));
        self::assertSame('fragment', $url->getFragment());
        self::assertTrue($url->isSecure());
        self::assertFalse($url->isRelative());
    }

    public function testQueryHelpersRemainImmutableAndSynchronizeWithPsrUri(): void
    {
        $original = new Url('/path?first=one');
        $changed = $original->mergeParams(['second' => 'two'])->removeParam('first');

        self::assertSame('/path?first=one', (string) $original);
        self::assertSame('/path?second=two', (string) $changed);
        self::assertSame('two', $changed->getParam('second'));
    }

    public function testPsrMutatorsPreserveTheConcreteType(): void
    {
        $url = new Url('/old');
        $changed = $url->withScheme('https')->withHost('example.com')->withPath('/new');

        self::assertInstanceOf(Url::class, $changed);
        self::assertSame('https://example.com/new', (string) $changed);
    }

    public function testMalformedUrisUseTheLibraryException(): void
    {
        $this->expectException(MalformedUrlException::class);

        new Url('https://example.com:99999');
    }
}
