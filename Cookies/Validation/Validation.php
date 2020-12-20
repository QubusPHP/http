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

namespace Qubus\Http\Cookies\Validation;

use RuntimeException;

use function chr;
use function hash_equals;
use function hash_hmac;
use function mt_rand;

class Validation
{
    public const DEFAULT_ALGO = 'sha256';
    public const NONCE_LENGTH = 32;

    private $key;
    private $algo;

    public function __construct($key, $algo = null)
    {
        $this->key = $key;
        $this->algo = $algo ?: static::DEFAULT_ALGO;
    }

    public function extract($value)
    {
        $message = Message::fromString($value);

        if (! $this->verify($message)) {
            throw new RuntimeException('Invalid message.');
        }

        return $message->getValue();
    }

    public function sign($value)
    {
        $nonce = $this->generateNonce();

        $hmac = hash_hmac($this->algo, $this->key, $nonce . $value);

        return $nonce . $value . '.' . $hmac;
    }

    private function verify(Message $message)
    {
        $calcualtedHmac = hash_hmac(
            $this->algo,
            $this->key,
            $message->getNonce() . $message->getValue()
        );

        return self::hashCompare($calcualtedHmac, $message->getHmac());
    }

    private static function generateNonce()
    {
        $result = '';
        for ($i = 0; $i < static::NONCE_LENGTH; $i++) {
            $result .= chr((mt_rand() ^ mt_rand()) % 256);
        }
        return $result;
    }

    private static function hashCompare($hash1, $hash2)
    {
        return hash_equals($hash1, $hash2);
    }
}
