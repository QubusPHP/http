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

namespace Qubus\Tests\Http\Cookies\Encryption\Adapter;

use PHPUnit\Framework\TestCase;
use Qubus\Http\Cookies\Encryption\Adapter\DefuseEncryption;
use Defuse\Crypto\Key;

class DefuseEncryptionTest extends TestCase
{
    protected function setUp()
    {
        if (! class_exists('Defuse\Crypto\Crypto')) {
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
