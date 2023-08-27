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

namespace Qubus\Http\Cookies\Encryption\Adapter;

use Defuse\Crypto\Crypto;
use Defuse\Crypto\Exception\EnvironmentIsBrokenException;
use Defuse\Crypto\Exception\WrongKeyOrModifiedCiphertextException;
use Defuse\Crypto\Key;
use Qubus\Http\Cookies\Encryption\Encryption;

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
    public function decrypt($value): string
    {
        return Crypto::Decrypt($value, $this->key);
    }

    /**
     * @throws EnvironmentIsBrokenException
     */
    public function encrypt($value): string
    {
        return Crypto::Encrypt($value, $this->key);
    }
}
