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

namespace Qubus\Http;

use Laminas\Diactoros\ServerRequestFactory as BaseServerRequestFactory;
use Psr\Http\Message\ServerRequestFactoryInterface;

final class ServerRequestFactory extends BaseServerRequestFactory implements ServerRequestFactoryInterface
{
}
