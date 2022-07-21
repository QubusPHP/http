<?php

/**
 * Qubus\Http
 *
 * @link       https://github.com/QubusPHP/http
 * @copyright  2022 Joshua Parker <josh@joshuaparker.blog>
 * @copyright  2016 Thomas Nordahl Pedersen <thno@jfmedier.dk>
 * @copyright  2016 Rasmus Schultz (aka mindplay-dk) <rasc@jfmedier.dk>
 * @copyright  2016 Bo Andersen <boan@jfmedier.dk>
 * @license    https://opensource.org/licenses/mit-license.php MIT License
 *
 * @since      2.0.0
 */

declare(strict_types=1);

namespace Qubus\Http\Session;

interface SessionEntity
{
    /**
     * Check if session entity is in an empty state.
     */
    public function isEmpty(): bool;
}
