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

namespace Qubus\Http\Session;

use Qubus\Exception\Data\TypeException;
use Qubus\ValueObjects\Identity\Uuid;

class ClientSessionId extends Uuid implements Validatable
{
    /**
     * Generate a Uuidv4 string.
     * @throws TypeException
     */
    public static function create(?string $id = null): string
    {
        return (new self($id))->__toString();
    }
}
