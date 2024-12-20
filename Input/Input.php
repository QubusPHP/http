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

namespace Qubus\Http\Input;

use function str_replace;
use function strtolower;
use function ucfirst;

class Input implements Item
{
    public string|int|null $index = null;

    public ?string $name = null;

    public ?string $value = null;

    public function __construct(string|int $index, ?string $value = null)
    {
        $this->index = (string) $index;
        $this->value = $value;
        // Make the name human friendly, by replace _ with space
        $this->name = ucfirst(str_replace('_', ' ', strtolower($this->index)));
    }

    public function getIndex(): string
    {
        return $this->index;
    }

    public function setIndex(string $index): Item
    {
        $this->index = $index;
        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * Set input name
     *
     * @return static
     */
    public function setName(string $name): Item
    {
        $this->name = $name;
        return $this;
    }

    public function getValue(): ?string
    {
        return $this->value;
    }

    /**
     * Set input value
     *
     * @return static
     */
    public function setValue(string $value): Item
    {
        $this->value = $value;
        return $this;
    }

    public function __toString(): string
    {
        return (string) $this->value;
    }
}
