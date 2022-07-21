<?php

/**
 * Qubus\Http
 *
 * @link       https://github.com/QubusPHP/http
 * @copyright  2022 Joshua Parker
 * @license    https://opensource.org/licenses/mit-license.php MIT License
 *
 * @since      2.0.0
 */

declare(strict_types=1);

namespace Qubus\Tests\Http\Session;

use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use Qubus\Http\Session\ClientSessionId;
use Qubus\Http\Session\HttpSession;
use Qubus\Http\Session\SessionData;
use Qubus\Http\Session\SessionId;
use Qubus\Tests\Http\Session\Entity\UserSession;

class SessionDataTest extends TestCase
{
    protected const USER_ID = '72f61cf4-5a84-4c7a-837d-fcadc9665471';
    protected string $clientSessionId;
    protected string $sessionId;
    protected HttpSession $session;

    public function setUp(): void
    {
        $this->clientSessionId = ClientSessionId::create();
        $this->sessionId = SessionId::create($this->clientSessionId);
        $this->session = SessionData::create($this->clientSessionId, [], true);
    }

    public function testClientSessionAndSessionId()
    {
        Assert::assertSame($this->clientSessionId, $this->session->clientSessionId());
        Assert::assertSame($this->sessionId, $this->session->sessionId());
    }

    public function testInstanceOfSessionEntity()
    {
        $model = $this->session->get(UserSession::class);
        $this->session->get(UserSession::class);

        Assert::assertInstanceOf(UserSession::class, $model, 'it creates a new model instance');

        $model->setId(self::USER_ID);

        Assert::assertSame($model, $this->session->get(UserSession::class), 'it returns the same instance every time');
    }

    public function testDataCanBeReturnedAndCleared()
    {
        $model = $this->session->get(UserSession::class);
        $model->setId(self::USER_ID);

        $data = $this->session->getData();

        $session = SessionData::create($this->clientSessionId, $data, false);

        Assert::assertEquals($model, $this->session->get(UserSession::class), 'it restores the entity instance from data');
        Assert::assertSame($model->userId(), $this->session->get(UserSession::class)->userId(), 'it preserves the entity state');

        $session->clear();

        Assert::assertSame([], $session->getData(), 'can clear session data');

        Assert::assertNotSame(
            $model,
            $session->get(UserSession::class),
            'it creates a new session entity after clear()'
        );
    }
}
