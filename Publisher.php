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
     * @param EmitterInterface|null $response
     * @return bool|ResponseInterface
     */
    public function publish(
        ResponseInterface|StreamInterface $content,
        ?EmitterInterface $response
    ): bool|ResponseInterface;
}
