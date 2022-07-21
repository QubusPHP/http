<?php

/**
 * Qubus\Http
 *
 * @link       https://github.com/QubusPHP/http
 * @copyright  2020 Joshua Parker <josh@joshuaparker.blog>
 * @copyright  2016 Simon Sessingø (aka skipperbent) <simon.sessingoe@gmail.com>
 * @license    https://opensource.org/licenses/mit-license.php MIT License
 *
 * @since      1.0.0
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
