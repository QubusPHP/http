<?php

/**
 * Qubus\Http
 *
 * @link       https://github.com/QubusPHP/http
 * @copyright  2022 Joshua Parker
 * @license    https://opensource.org/licenses/mit-license.php MIT License
 *
 * @since      2.0.0
 */

declare(strict_types=1);

namespace Qubus\Http\Session;

use Qubus\Exception\Data\TypeException;
use Qubus\ValueObjects\Identity\Uuid;

class ClientSessionId extends Uuid implements Validatable
{
    /**
     * Generate a Uuidv4 string.
     */
    public static function create(?string $id = null): string
    {
        $sessionId = (new self($id))->__toString();

        if($sessionId instanceof TypeException) {
            throw new TypeException('String is not a valid Uuidv4 id.');
        }

        return $sessionId;
    }
}
