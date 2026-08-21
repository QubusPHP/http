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

use Qubus\Exception\Data\TypeException;
use RuntimeException;

use function array_merge;
use function file_get_contents;
use function is_numeric;
use function is_string;
use function move_uploaded_file;
use function pathinfo;
use function sprintf;
use function str_replace;
use function strtolower;
use function ucfirst;

use const PATHINFO_EXTENSION;

class File implements Item
{
    public string|int $index;
    public string $name;
    public ?string $filename = null;
    public ?int $size = 0;
    public ?string $type = 'application/octet-stream';
    public int $errors = 0;
    public ?string $tmpName = '';

    public function __construct(string|int $index)
    {
        $this->index  = (string) $index;
        $this->errors = 0;
        // Make the name human friendly, by replace _ with space
        $this->name = ucfirst(str_replace('_', ' ', strtolower($this->index)));
    }

    /**
     * Create from array
     *
     * @param array $values
     * @return self
     * @throws TypeException
     */
    public static function createFromArray(array $values): self
    {
        if (isset($values['index']) === false) {
            throw new TypeException('Index key is required');
        }

        foreach (['name', 'tmp_name', 'type'] as $key) {
            if (! isset($values[$key]) || ! is_string($values[$key])) {
                throw new TypeException(sprintf('The upload %s key must be a string.', $key));
            }
        }

        foreach (['size', 'error'] as $key) {
            if (! isset($values[$key]) || ! is_numeric($values[$key])) {
                throw new TypeException(sprintf('The upload %s key must be numeric.', $key));
            }
        }

        /* Easy way of ensuring that all indexes-are set and not filling the screen with isset() */
        $extended = [
            'tmp_name' => null,
            'type'     => null,
            'size'     => null,
            'name'     => null,
            'error'    => null,
        ];

        $values = array_merge($extended, $values);

        return new self($values['index'])
            ->setSize((int) $values['size'])
            ->setError((int) $values['error'])
            ->setType($values['type'])
            ->setTmpName($values['tmp_name'])
            ->setFilename($values['name']);
    }

    public function getIndex(): string
    {
        return $this->index;
    }

    /**
     * Set input index
     *
     * @return static
     */
    public function setIndex(string $index): Item
    {
        $this->index = $index;
        return $this;
    }

    public function getSize(): int
    {
        return $this->size ?? 0;
    }

    /**
     * Set file size
     *
     * @return static
     */
    public function setSize(int $size): Item
    {
        $this->size = $size;
        return $this;
    }

    /**
     * Get mime-type of file
     */
    public function getMime(): string
    {
        return $this->getType();
    }

    public function getType(): string
    {
        return $this->type ?? 'application/octet-stream';
    }

    /**
     * Set type
     *
     * @return static
     */
    public function setType(string $type): Item
    {
        $this->type = $type;
        return $this;
    }

    /**
     * Returns extension without "."
     */
    public function getExtension(): string
    {
        return pathinfo($this->getFilename(), PATHINFO_EXTENSION);
    }

    /**
     * Get human friendly name
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * Set human friendly name.
     * Useful for adding validation etc.
     *
     * @return static
     */
    public function setName(string $name): Item
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Set filename
     *
     * @param string $name
     * @return static
     */
    public function setFilename(string $name): Item
    {
        $this->filename = $name;
        return $this;
    }

    /**
     * Get filename
     *
     * @return null|string mixed
     */
    public function getFilename(): ?string
    {
        return $this->filename;
    }

    /**
     * Move the uploaded temporary file to it's new home
     *
     * @param string $destination
     * @return bool
     */
    public function move(string $destination): bool
    {
        if ($this->hasError() || $this->tmpName === null || $this->tmpName === '') {
            return false;
        }

        return move_uploaded_file($this->tmpName, $destination);
    }

    /**
     * Get file contents
     */
    public function getContents(): string
    {
        if ($this->tmpName === null || $this->tmpName === '') {
            throw new RuntimeException('The uploaded temporary file path is empty.');
        }

        $contents = file_get_contents($this->tmpName);

        if ($contents === false) {
            throw new RuntimeException('Unable to read the uploaded temporary file.');
        }

        return $contents;
    }

    /**
     * Return true if an upload error occurred.
     */
    public function hasError(): bool
    {
        return $this->getError() !== 0;
    }

    /**
     * Get upload-error code.
     */
    public function getError(): ?int
    {
        return $this->errors;
    }

    /**
     * Set error
     *
     * @return static
     */
    public function setError(?int $error = null): Item
    {
        $this->errors = $error ?? 0;
        return $this;
    }

    public function getTmpName(): string
    {
        return $this->tmpName ?? '';
    }

    /**
     * Set file temp. name
     *
     * @param string $name
     * @return static
     */
    public function setTmpName(string $name): Item
    {
        $this->tmpName = $name;
        return $this;
    }

    public function __toString(): string
    {
        return $this->getTmpName();
    }

    public function getValue(): ?string
    {
        return $this->getFilename();
    }

    /**
     * @return static
     */
    public function setValue(string $value): Item
    {
        $this->filename = $value;
        return $this;
    }

    public function toArray(): array
    {
        return [
            'tmp_name' => $this->tmpName,
            'type'     => $this->type,
            'size'     => $this->size,
            'name'     => $this->name,
            'error'    => $this->errors,
            'filename' => $this->filename,
        ];
    }
}
