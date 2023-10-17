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

namespace Qubus\Http\Cookies\Factory;

use Qubus\Exception\Data\TypeException;
use Qubus\Exception\Exception;
use Qubus\Http\Cookies\SetCookieCollection;

interface HttpCookieFactory
{
    /**
     * Make a new cookie instance.
     *
     * This method returns a cookie instance for use with the Set-Cookie HTTP header.
     * @throws TypeException
     * @throws Exception
     */
    public function make(string $name, ?string $value = null, ?int $maxAge = null): SetCookieCollection;

    /**
     * Make an expired cookie instance.
     */
    public function expire(string $name): SetCookieCollection|string;
}
