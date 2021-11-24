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
