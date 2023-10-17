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

namespace Qubus\Http\Session\Middleware;

use Exception as GlobalException;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Qubus\Exception\Data\TypeException;
use Qubus\Exception\Exception;
use Qubus\Http\Session\SessionService;

final class SessionMiddleware implements MiddlewareInterface
{
    public const SESSION_ATTRIBUTE = 'qubus.session';

    public function __construct(public readonly SessionService $sessionService)
    {
    }

    /**
     * {@inheritDoc}
     *
     * @throws Exception
     * @throws TypeException
     * @throws InvalidArgumentException
     * @throws GlobalException
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $session = $this->sessionService->makeSession(request: $request);

        $request = $request
            ->withAttribute(self::SESSION_ATTRIBUTE, $session);

        $response = $handler->handle($request);

        return $this->sessionService->commitSession(response: $response, session: $session);
    }
}
