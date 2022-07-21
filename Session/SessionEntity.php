<?php

/**
 * Qubus\Http
 *
 * @link       https://github.com/QubusPHP/http
 * @copyright  2022 Joshua Parker
 * @copyright  2020 Rasmus Schultz (aka mindplay-dk)
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
     * 
     * @return bool 
     */
    public function isEmpty(): bool;
}
