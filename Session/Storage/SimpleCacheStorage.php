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

namespace Qubus\Http\Session\Storage;

use Psr\SimpleCache\CacheInterface;

class SimpleCacheStorage implements SessionStorage
{
    public function __construct(protected CacheInterface $cache)
    { 
    }

    /**
     * {@inheritDoc}
     */
    public function read(string $sessionId): ?array
    {
        return $this->cache->get($sessionId);
    }

    /**
     * {@inheritDoc}
     */
    public function write(string $sessionId, array $data, int $ttl): void
    {
        $this->cache->set($sessionId, $data, $ttl);
    }

    /**
     * {@inheritDoc}
     */
    public function destroy(string $sessionId): void
    {
        $this->cache->delete($sessionId);
    }
}