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

namespace Qubus\Tests\Http\Cookies;

use Psr\Http\Message\ResponseInterface;
use RuntimeException;

class CookieResponseTesting implements ResponseInterface
{
    use CookieMessageTesting;

    public function getStatusCode(): void
    {
        throw new RuntimeException('This method has not been implemented.');
    }

    /**
     * @param int    $code
     * @param string $reasonPhrase
     */
    public function withStatus($code, $reasonPhrase = ''): void
    {
        throw new RuntimeException('This method has not been implemented.');
    }

    public function getReasonPhrase(): void
    {
        throw new RuntimeException('This method has not been implemented.');
    }
}
