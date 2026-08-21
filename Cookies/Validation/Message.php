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

use InvalidArgumentException;

use function strlen;
use function strrpos;
use function substr;

readonly class Message
{
    private function __construct(
        public string $nonce,
        public string $hmac,
        public string $value,
    ) {
    }

    public function getNonce(): string
    {
        return $this->nonce;
    }

    public function getHmac(): string
    {
        return $this->hmac;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public static function fromString(string $value): Message
    {
        $separator = strrpos($value, '.');

        if ($separator === false || $separator < 32 || $separator === strlen($value) - 1) {
            throw new InvalidArgumentException('The signed cookie value is malformed.');
        }

        $nonce = substr($value, 0, 32);
        $hmac = substr($value, $separator + 1);
        $value = substr($value, 32, strlen($value) - strlen($hmac) - 33);

        return new self($nonce, $hmac, $value);
    }
}
