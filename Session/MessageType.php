<?php

/**
 * Qubus\Http
 *
 * @link       https://github.com/QubusPHP/http
 * @copyright  2022
 * @author     Joshua Parker <joshua@joshuaparker.dev>
 * @license    https://opensource.org/licenses/mit-license.php MIT License
 */

declare(strict_types=1);

namespace Qubus\Http\Session;

class MessageType
{
    // Message types and shortcuts
    public const INFO = 'i';
    public const SUCCESS = 's';
    public const WARNING = 'w';
    public const ERROR = 'e';
    // Default message type
    public const DEFAULT = self::INFO;
}
