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

namespace Qubus\Http\Cookies\Encryption\Adapter;

use Defuse\Crypto\Crypto;
use Defuse\Crypto\Key;
use Qubus\Http\Cookies\Encryption\Encryption;

class DefuseEncryption implements Encryption
{
    /**
     * @param string $key
     */
    public function __construct(public readonly Key $key)
    {
    }

    public function decrypt($value)
    {
        return Crypto::Decrypt($value, $this->key);
    }

    public function encrypt($value)
    {
        return Crypto::Encrypt($value, $this->key);
    }
}
