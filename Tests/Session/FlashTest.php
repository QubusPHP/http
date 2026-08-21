<?php

declare(strict_types=1);

namespace Qubus\Tests\Http\Session;

use PHPUnit\Framework\TestCase;
use Qubus\Http\Session\Flash;
use Qubus\Http\Session\PhpSession;

final class FlashTest extends TestCase
{
    public function testMessagesAreStoredThroughTheSessionAndEscapedByDefault(): void
    {
        $flash = new Flash($this->createSession());
        $flash->error('<script>alert("x")</script>');

        self::assertTrue($flash->hasErrors());
        self::assertTrue($flash->hasMessages());

        $output = $flash->display(print: false);

        self::assertIsString($output);
        self::assertStringContainsString('&lt;script&gt;', $output);
        self::assertStringNotContainsString('<script>', $output);
        self::assertFalse($flash->hasMessages());
    }

    public function testTrustedMarkupCanBeEnabledExplicitly(): void
    {
        $flash = new Flash($this->createSession());
        $flash->setEscapeHtml(false)->info('<strong>Saved</strong>');

        self::assertStringContainsString('<strong>Saved</strong>', $flash->display(print: false));
    }

    private function createSession(): PhpSession
    {
        return new class () implements PhpSession {
            private array $data = [];

            public function isSessionActive(): bool
            {
                return true;
            }

            public function startSession(): void
            {
            }

            public function has(string $name): bool
            {
                return isset($this->data[$name]);
            }

            public function get(string $name): string|array
            {
                return $this->data[$name] ?? [];
            }

            public function set(string $name, mixed $value): void
            {
                $this->data[$name] = $value;
            }

            public function getAll(): array
            {
                return $this->data;
            }

            public function unsetSession(string $key): void
            {
                unset($this->data[$key]);
            }
        };
    }
}
