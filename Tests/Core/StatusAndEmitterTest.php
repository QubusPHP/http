<?php

declare(strict_types=1);

namespace Qubus\Tests\Http\Core;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Qubus\Http\Emitter\ContentRange;
use Qubus\Http\Emitter\Exceptions\EmitterException;
use Qubus\Http\Emitter\SapiStreamEmitter;
use Qubus\Http\Status;

final class StatusAndEmitterTest extends TestCase
{
    public function testUnknownStatusMessagesAreSafe(): void
    {
        self::assertSame('', Status::getMessageForCode(999));
        self::assertFalse(Status::isError(999));
        self::assertTrue(Status::isSuccessful(204));
        self::assertTrue(Status::isRedirect(308));
        self::assertTrue(Status::isClientError(404));
        self::assertTrue(Status::isServerError(503));
    }

    public function testStreamEmitterAcceptsPositiveBufferSizes(): void
    {
        $emitter = new SapiStreamEmitter()->setMaxBufferSize(16384);

        self::assertSame(16384, $emitter->getMaxBufferSize());
    }

    #[DataProvider('invalidRangeProvider')]
    public function testRejectsInvalidContentRanges(int $start, int $end, ?int $size): void
    {
        $this->expectException(EmitterException::class);

        new ContentRange($start, $end, $size);
    }

    public static function invalidRangeProvider(): array
    {
        return [
            'negative start' => [-1, 1, 2],
            'end before start' => [2, 1, 3],
            'end beyond size' => [0, 3, 3],
        ];
    }
}
