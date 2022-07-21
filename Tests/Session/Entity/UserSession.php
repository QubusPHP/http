<?php

/**
 * Qubus\Http
 *
 * @link       https://github.com/QubusPHP/http
 * @copyright  2022 Joshua Parker <josh@joshuaparker.blog>
 * @license    https://opensource.org/licenses/mit-license.php MIT License
 *
 * @since      2.0.0
 */

declare(strict_types=1);

namespace Qubus\Tests\Http\Session\Entity;

use Qubus\Http\Session\SessionEntity;

class UserSession implements SessionEntity
{
    public function __construct(protected ?string $userId = null)
    {
    }

    public function setId(?string $userId = null): void
    {
        $this->userId = $userId;
    }

    public function userId(): string|null
    {
        return $this->userId;
    }

    public function isEmpty(): bool
    {
        return empty($this->userId);
    }
}
