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
    public const string INFO = 'i';
    public const string SUCCESS = 's';
    public const string WARNING = 'w';
    public const string ERROR = 'e';
    // Default message type
    public const string DEFAULT = self::INFO;
}
