<?php

/**
 * Qubus\Http
 *
 * @link       https://github.com/QubusPHP/http
 * @copyright  2020 Joshua Parker <josh@joshuaparker.blog>
 * @license    https://opensource.org/licenses/mit-license.php MIT License
 *
 * @since      1.0.0
 */

declare(strict_types=1);

namespace Qubus\Tests\Http\Cookies\Encryption\Adapter;

use Defuse\Crypto\Crypto;
use Defuse\Crypto\Key;
use PHPUnit\Framework\TestCase;
use Qubus\Http\Cookies\Encryption\Adapter\DefuseEncryption;

use function class_exists;
use function str_repeat;

class DefuseEncryptionTest extends TestCase
{
    protected function setUp(): void
    {
        if (! class_exists(Crypto::class)) {
            $this->markTestSkipped('Missing defuse/php-encryption');
        }
    }

    public function testEncryptAndDecrypt()
    {
        $encryption = new DefuseEncryption(Key::createNewRandomKey());

        $size = 10 * 1024;
        $plaintext = str_repeat('a', $size);
        $encrypted = $encryption->encrypt($plaintext);

        $this->assertEquals($plaintext, $encryption->decrypt($encrypted));
    }
}
