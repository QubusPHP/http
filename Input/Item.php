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

interface Item
{
    public function getIndex(): string;

    public function setIndex(string $index): self;

    public function getName(): ?string;

    public function setName(string $name): self;

    public function getValue(): ?string;

    public function setValue(string $value): self;

    public function __toString(): string;
}
