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

use ArrayAccess;
use ArrayIterator;
use IteratorAggregate;
use Traversable;

use function json_encode;
use function str_replace;
use function strtolower;
use function ucfirst;

class Input implements Item, ArrayAccess, IteratorAggregate
{
    public string|int|null $index = null;

    public ?string $name = null;

    public string|array|null $value = null;

    public function __construct(string|int $index, string|array|null $value = null)
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

    public function getValue(): string|array|null
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
        return json_encode($this->value);
    }

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->value[$offset]);
    }

    public function offsetGet(mixed $offset): mixed
    {
        if ($this->offsetExists($offset) === true) {
            return $this->value[$offset];
        }

        return null;
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->value[$offset] = $value;
    }

    public function offsetUnset(mixed $offset): void
    {
        unset($this->value[$offset]);
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->getValue());
    }
}
