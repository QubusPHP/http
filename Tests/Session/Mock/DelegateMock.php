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

namespace Qubus\Tests\Http\Session\Mock;

use Closure;
use Laminas\Diactoros\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

use function call_user_func_array;

class DelegateMock implements RequestHandlerInterface
{
    /** @var Closure */
    public Closure $next;

    public function __construct(Closure $next)
    {
        $this->next = $next;
    }

    /**
     * Dispatch the next available middleware and return the response.
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $response = call_user_func_array($this->next, [$request]);

        return $response instanceof ResponseInterface ? $response : new Response();
    }
}
