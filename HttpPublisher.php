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

use Exception;
use Laminas\HttpHandlerRunner\Emitter\EmitterInterface;
use LogicException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Qubus\Http\Factories\HtmlResponseFactory;

use function flush;
use function function_exists;
use function header;
use function sprintf;
use function ucwords;

/**
 * StreamPublisher publishes the given response.
 */
class HttpPublisher implements Publisher
{
    /**
     * {@inheritdoc}
     *
     * @throws LogicException|Exception
     */
    public function publish(
        ResponseInterface|StreamInterface $content,
        ?EmitterInterface $emitter
    ): bool|ResponseInterface {
        $content = empty($content) ? '' : $content;

        if (null !== $emitter && $content instanceof ResponseInterface) {
            try {
                return $emitter->emit($content);
            } finally {
                if (function_exists('fastcgi_finish_request')) {
                    fastcgi_finish_request();
                }
            }
        }

        if (null === $emitter && $content instanceof ResponseInterface) {
            $this->emitResponseHeaders($content);
            $content = $content->getBody();
        }

        flush();

        if ($content instanceof StreamInterface) {
            try {
                return $this->emitStreamBody($content);
            } finally {
                if (function_exists('fastcgi_finish_request')) {
                    fastcgi_finish_request();
                }
            }
        }
        return HtmlResponseFactory::create(
            'The response body must be an instance of ResponseInterface or StreamInterface',
            200,
            ['Content-Type' => ['application/xhtml+xml']]
        );
    }

    /**
     * Emit the message body.
     */
    private function emitStreamBody(StreamInterface $body): bool
    {
        if ($body->isSeekable()) {
            $body->rewind();
        }

        if (! $body->isReadable()) {
            echo $body;

            return true;
        }

        while (! $body->eof()) {
            echo $body->read(8192);
        }

        return true;
    }

    /**
     * Emit the response header.
     */
    private function emitResponseHeaders(ResponseInterface $response): void
    {
        $statusCode = $response->getStatusCode();

        foreach ($response->getHeaders() as $name => $values) {
            $name  = ucwords($name, '-'); // Filter a header name to wordcase
            $first = $name !== 'Set-Cookie';

            foreach ($values as $value) {
                header(sprintf('%s: %s', $name, $value), $first, $statusCode);
                $first = false;
            }
        }
    }
}
