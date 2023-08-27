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

    public static function fromString($value): Message
    {
        $nonce = substr($value, 0, 32);
        $hmac = substr($value, strrpos($value, '.') + 1);
        $value = substr($value, 32, strlen($value) - strlen($hmac) - 33);

        return new self($nonce, $hmac, $value);
    }
}
