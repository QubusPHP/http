<?php

declare(strict_types=1);

namespace Qubus\Tests\Http\Core;

use PHPUnit\Framework\TestCase;
use Qubus\Exception\Data\TypeException;
use Qubus\Http\Input\File;
use Qubus\Http\Input\Input;
use RuntimeException;

final class InputTest extends TestCase
{
    public function testScalarInputCanBeUsedSafelyAsArrayAndIterator(): void
    {
        $input = new Input('name', 'Ada');

        self::assertSame('Ada', (string) $input);
        self::assertSame([], iterator_to_array($input));
        self::assertFalse(isset($input['missing']));

        $input[] = 'first';
        self::assertSame(['first'], $input->getValue());
    }

    public function testUploadedFileMetadataMustBeComplete(): void
    {
        $this->expectException(TypeException::class);

        File::createFromArray(['index' => 'avatar']);
    }

    public function testUnreadableUploadedFileFailsClearly(): void
    {
        $this->expectException(RuntimeException::class);

        new File('avatar')->getContents();
    }
}
