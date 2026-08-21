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

namespace Qubus\Http\Cookies\Validation;

use Random\RandomException;
use RuntimeException;
use InvalidArgumentException;

use function hash_equals;
use function hash_hmac;
use function hash_hmac_algos;
use function in_array;
use function random_bytes;

class Validation
{
    public const string DEFAULT_ALGO = 'sha256';
    public const int NONCE_LENGTH = 32;

    public readonly string $key;
    public readonly string $algo;

    public function __construct(string $key, ?string $algo = null)
    {
        $this->key = $key;
        $this->algo = $algo ?: static::DEFAULT_ALGO;

        if ($key === '') {
            throw new InvalidArgumentException('The cookie signing key cannot be empty.');
        }

        if (! in_array($this->algo, hash_hmac_algos(), true)) {
            throw new InvalidArgumentException('The requested HMAC algorithm is not supported.');
        }
    }

    public function extract(string $value): string
    {
        $message = Message::fromString($value);

        if (! $this->verify($message)) {
            throw new RuntimeException('Invalid message.');
        }

        return $message->getValue();
    }

    /**
     * @throws RandomException
     */
    public function sign(string $value): string
    {
        $nonce = $this->generateNonce();

        $hmac = hash_hmac($this->algo, $this->key, $nonce . $value);

        return $nonce . $value . '.' . $hmac;
    }

    private function verify(Message $message): bool
    {
        $calculatedHmac = hash_hmac(
            $this->algo,
            $this->key,
            $message->getNonce() . $message->getValue()
        );

        return self::hashCompare($calculatedHmac, $message->getHmac());
    }

    /**
     * @throws RandomException
     */
    private static function generateNonce(): string
    {
        return random_bytes(static::NONCE_LENGTH);
    }

    private static function hashCompare(string $hash1, string $hash2): bool
    {
        return hash_equals($hash1, $hash2);
    }
}
