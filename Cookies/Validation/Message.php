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

namespace Qubus\Http\Cookies\Validation;

use function strlen;
use function strrpos;
use function substr;

class Message
{
    private function __construct(
        public readonly string $nonce,
        public readonly string $hmac,
        public readonly string $value,
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

    public static function fromString($value): Message
    {
        $nonce = substr($value, 0, 32);
        $hmac = substr($value, strrpos($value, '.') + 1);
        $value = substr($value, 32, strlen($value) - strlen($hmac) - 33);

        return new self($nonce, $hmac, $value);
    }
}
