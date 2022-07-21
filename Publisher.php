<?php

/**
 * Qubus\Http
 *
 * @link       https://github.com/QubusPHP/http
 * @copyright  2020 Joshua Parker <josh@joshuaparker.blog>
 * @license    https://opensource.org/licenses/mit-license.php MIT License
 *
 * @since      1.0.0
 */

declare(strict_types=1);

namespace Qubus\Http;

use Laminas\HttpHandlerRunner\Emitter\EmitterInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

interface Publisher
{
    /**
     * Publish the content.
     *
     * @param ResponseInterface|StreamInterface $content
     * @param null|EmitterInterface             $emitter
     */
    public function publish(ResponseInterface|StreamInterface $content, ?EmitterInterface $response): bool;
}
