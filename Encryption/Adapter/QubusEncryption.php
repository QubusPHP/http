<?php

/**
 * Qubus\Http
 *
 * @link       https://github.com/QubusPHP/http
 * @copyright  2020
 * @author     Joshua Parker <joshua@joshuaparker.dev>
 * @license    https://opensource.org/licenses/mit-license.php MIT License
 */

declare(strict_types=1);

namespace Qubus\Http\Encryption\Adapter;

use Defuse\Crypto\Crypto as DefuseCrypto;
use Defuse\Crypto\Exception\EnvironmentIsBrokenException;
use Defuse\Crypto\Exception\WrongKeyOrModifiedCiphertextException;
use Defuse\Crypto\Key;
use Qubus\Http\Encryption\Encryption;

readonly class DefuseEncryption implements Encryption
{
    /**
     * @param Key $key
     */
    public function __construct(public Key $key)
    {
    }

    /**
     * @throws WrongKeyOrModifiedCiphertextException
     * @throws EnvironmentIsBrokenException
     */
    public function decrypt(string $value, bool $rawBinary = false): string
    {
        return DefuseCrypto::Decrypt($value, $this->key, $rawBinary);
    }

    /**
     * @throws EnvironmentIsBrokenException
     */
    public function encrypt(string $value, bool $rawBinary = false): string
    {
        return DefuseCrypto::Encrypt($value, $this->key, $rawBinary);
    }
}
