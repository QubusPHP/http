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

namespace Qubus\Http\Cookies;

use Qubus\Exception\Data\TypeException;

use function sprintf;
use function strtolower;

final class SameSite
{
    /**
     * The possible string values of the SameSite setting
     */
    private const STRICT = 'Strict';
    private const LAX    = 'Lax';
    private const NONE   = 'None';

    private ?string $value = null;

    private function __construct(string $value)
    {
        $this->value = $value;
    }

    public static function strict(): self
    {
        return new self(self::STRICT);
    }

    public static function lax(): self
    {
        return new self(self::LAX);
    }

    public static function none(): self
    {
        return new self(self::NONE);
    }

    /**
     * Undocumented function
     *
     * @throws TypeException If the given SameSite string is neither strict, lax or none.
     */
    public static function fromString(string $sameSite): self
    {
        $lowerCaseSite = strtolower($sameSite);

        if ($lowerCaseSite === 'strict') {
            return self::strict();
        }

        if ($lowerCaseSite === 'lax') {
            return self::lax();
        }

        if ($lowerCaseSite === 'none') {
            return self::none();
        }

        throw new TypeException(
            sprintf(
                'Expected modifier value to be either "strict", "lax", or "none", "%s" given',
                $sameSite
            )
        );
    }

    public function asString(): string
    {
        return 'SameSite=' . $this->value;
    }
}
