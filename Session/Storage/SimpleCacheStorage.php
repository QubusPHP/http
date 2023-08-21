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

namespace Qubus\Http\Session\Storage;

use Psr\SimpleCache\CacheInterface;
use Psr\SimpleCache\InvalidArgumentException;

class SimpleCacheStorage implements SessionStorage
{
    public function __construct(protected CacheInterface $cache)
    {
    }

    /**
     * {@inheritDoc}
     * @throws InvalidArgumentException
     */
    public function read(string $sessionId): ?array
    {
        return $this->cache->get($sessionId);
    }

    /**
     * {@inheritDoc}
     * @throws InvalidArgumentException
     */
    public function write(string $sessionId, array $data, int $ttl): void
    {
        $this->cache->set($sessionId, $data, $ttl);
    }

    /**
     * {@inheritDoc}
     * @throws InvalidArgumentException
     */
    public function destroy(string $sessionId): void
    {
        $this->cache->delete($sessionId);
    }
}
