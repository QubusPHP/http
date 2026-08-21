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

namespace Qubus\Tests\Http\Session;

use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Qubus\Cache\FileSystemCache;
use Qubus\Config\Collection;
use Qubus\Exception\Data\TypeException;
use Qubus\Exception\Exception;
use Qubus\FileSystem\Adapter\LocalFlysystemAdapter;
use Qubus\FileSystem\FileSystem;
use Qubus\Http\Cookies\Factory\CookieFactory;
use Qubus\Http\Response;
use Qubus\Http\ServerRequest;
use Qubus\Http\Session\HttpSession;
use Qubus\Http\Session\Middleware\SessionMiddleware;
use Qubus\Http\Session\SessionService;
use Qubus\Http\Session\Storage\SessionStorage;
use Qubus\Http\Session\Storage\SimpleCacheStorage;
use Qubus\Tests\Http\Session\Entity\UserSession;
use Qubus\Tests\Http\Session\Mock\DelegateMock;

use function mb_strpos;
use function mb_substr;

class SessionMiddlewareTest extends TestCase
{
    protected const string USER_ID = '72f61cf4-5a84-4c7a-837d-fcadc9665471';
    protected SessionStorage $storage;
    protected CookieFactory $cookie;
    protected MiddlewareInterface $middleware;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        $config = Collection::factory([
            'path' => __DIR__ . '/../config',
        ]);

        $localAdapter = new LocalFlysystemAdapter($config);
        $filesystem = new FileSystem($localAdapter);

        $this->storage = new SimpleCacheStorage(new FileSystemCache($filesystem, 160));
        $this->cookie = new CookieFactory($config);
        $this->middleware = new SessionMiddleware(new SessionService($this->storage, new CookieFactory($config)));
    }

    /**
     * @throws TypeException
     * @throws Exception
     */
    public function testGenerateSessionEntity()
    {
        $userEntity = null;

        $delegate = new DelegateMock(function (ServerRequestInterface $request) use (&$userEntity) {
            $session = $request->getAttribute(SessionMiddleware::SESSION_ATTRIBUTE);
            $sessionObject = $request->getAttribute(SessionMiddleware::SESSION_OBJECT_ATTRIBUTE);

            Assert::assertNotEmpty($session);
            Assert::assertIsString($session);
            Assert::assertInstanceOf(HttpSession::class, $sessionObject);
            Assert::assertFalse($request->hasHeader('X-Session-Id'));

            return new Response();
        });

        $requestOne = new ServerRequest();

        $response = $this->middleware->process($requestOne, $delegate);

        $cookies = $this->getCookies($response);

        $requestTwo = new ServerRequest()->withCookieParams($cookies);

        $this->middleware->process($requestTwo, $delegate);
    }

    private function getCookies(ResponseInterface $response): array
    {
        $cookieHeaders = $response->getHeader("Set-Cookie");

        $cookies = [];

        foreach ($cookieHeaders as $cookieString) {
            $cookiePair = mb_substr($cookieString, 0, mb_strpos($cookieString, ";"));

            $cookieKey = mb_substr($cookiePair, 0, mb_strpos($cookiePair, "="));
            $cookieValue = mb_substr($cookiePair, mb_strpos($cookiePair, "=") + 1);

            $cookies[$cookieKey] = $cookieValue;
        }

        return $cookies;
    }
}
