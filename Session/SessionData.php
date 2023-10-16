<?php

/**
 * Qubus\Http
 *
 * @link       https://github.com/QubusPHP/http
 * @copyright  2023
 * @author     Joshua Parker <joshua@joshuaparker.dev>
 * @license    https://opensource.org/licenses/mit-license.php MIT License
 */

declare(strict_types=1);

namespace Qubus\Http\Session;

use Exception;
use Qubus\Exception\Data\TypeException;
use ReflectionClass;
use ReflectionException;

use function class_exists;
use function md5_file;
use function Qubus\Support\Helpers\is_null__;
use function sprintf;

final class SessionData implements HttpSession
{
    /** @var string|null Old session id if the session was renewed. */
    private ?string $oldSessionId = null;

    /** @var SessionEntity[] */
    private array $objects = [];

    public function __construct(
        /** @var string client session id. */
        private string $clientSessionId,
        private array $data,
        private bool $isNew = false,
    ) {
    }

    /**
     * {@inheritDoc}
     * @throws Exception
     */
    public function sessionId(): string
    {
        return SessionId::create($this->clientSessionId);
    }

    /**
     * {@inheritDoc}
     */
    public function clientSessionId(): string
    {
        return $this->clientSessionId;
    }

    /**
     * {@inheritDoc}
     * @throws ReflectionException
     */
    public function getData(): array
    {
        $data = $this->data;

        foreach ($this->objects as $object) {
            $type = $object::class;

            if ($object->isEmpty()) {
                unset($data[$type]);
            } else {
                $data[$type] = [$this->checksum($type), serialize($object)];
            }
        }

        return $data;
    }

    /**
     * {@inheritDoc}
     * @throws TypeException|ReflectionException
     */
    public function get(string $type): SessionEntity
    {
        if (! class_exists($type)) {
            throw new TypeException(sprintf('The class %s does not exist.', $type));
        }

        if (! isset($this->objects[$type])) {
            if (isset($this->data[$type])) {
                [$checksum, $serialized] = $this->data[$type];

                $this->objects[$type] = $checksum === $this->checksum($type)
                ? unserialize($serialized)
                : new $type();
            } else {
                $this->objects[$type] = new $type();
            }
        }

        return $this->objects[$type];
    }

    /**
     * {@inheritDoc}
     * @throws Exception
     */
    public function clear(): void
    {
        $this->data = [];
        $this->objects = [];

        // in case data is added to the session after clearing it, we'll consider that a new
        // session - renewing the Session ensures a new Session ID gets assigned in that case:

        $this->renew();
    }

    /**
     * {@inheritDoc}
     * @throws Exception
     */
    public function renew(): void
    {
        if (! $this->isRenewed()) {
            $this->oldSessionId = $this->sessionID();
            $this->clientSessionId = ClientSessionId::create();
        }
    }

    public function isNew(): bool
    {
        return $this->isNew;
    }

    public function isRenewed(): bool
    {
        return ! is_null__($this->oldSessionId);
    }

    public function oldSessionID(): ?string
    {
        return $this->oldSessionId;
    }

    /**
     * Internally checksum a class implementation.
     *
     * Any change to the class source-file will cause invalidation of the session-model, such
     * that changes to the code will effectively cause session-models to re-initialize to their
     * default state - this is necessary because even a change to a type-hint in a doc-block
     * could cause an unserialize() call to inject the wrong type of value.
     *
     * @param string $type fully-qualified class-name
     * @return string MD5 checksum
     * @throws ReflectionException
     */
    protected function checksum(string $type): string
    {
        static $checksum = [];

        if (! isset($checksum[$type])) {
            $reflection = new ReflectionClass($type);

            $checksum[$type] = md5_file($reflection->getFileName());
        }

        return $checksum[$type];
    }
}
