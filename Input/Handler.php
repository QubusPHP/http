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
use Qubus\Http\Request;

use function array_flip;
use function array_intersect_key;
use function array_shift;
use function count;
use function file_get_contents;
use function in_array;
use function is_array;
use function json_decode;
use function json_last_error;
use function parse_str;
use function str_starts_with;
use function trim;

class Handler
{
    /** @var array $get */
    protected array $get = [];

    /** @var array $post */
    protected array $post = [];

    /** @var array $file */
    protected array $file = [];

    //phpcs:disable
    /**
     * Original post variables.
     * @var array
     */
    public array $originalPost = [] {
        get => $this->originalPost;
    }

    /**
     * Original get/params variables.
     * @var array
     */
    public array $originalParams = [] {
        get => $this->originalParams;
    }

    /**
     * Get original file variables.
     * @var array
     */
    protected array $originalFile = [] {
        get => $this->originalFile;
    }
    //phpcs:enable

    public function __construct(public readonly Request $request)
    {
        $this->parseInputs();
    }

    /**
     * Parse input values.
     */
    public function parseInputs(): void
    {
        /* Parse get requests */
        $this->originalParams = $_GET;
        if ($this->originalParams === [] && $this->request->getUri()->getQuery() !== '') {
            $queryParams = [];
            parse_str($this->request->getUri()->getQuery(), $queryParams);
            $this->originalParams = $queryParams;
        }

        if (count($this->originalParams) !== 0) {
            $this->get = $this->parseInputItem($this->originalParams);
        }

        /* Parse post requests */
        $this->originalPost = $_POST;

        if ($this->request->isPostBack() === true) {
            $body = $this->request->getBody();
            $position = $body->isSeekable() ? $body->tell() : null;

            if ($body->isSeekable()) {
                $body->rewind();
            }

            $contents = $body->getContents();

            if ($position !== null) {
                $body->seek($position);
            }

            if ($contents === '') {
                $contents = (string) file_get_contents(filename: 'php://input');
            }

            // Append any PHP-input json
            $trimmedContents = trim(string: $contents);
            if (
                $this->request->getContentType() === Request::CONTENT_TYPE_JSON
                || str_starts_with($trimmedContents, '{')
                || str_starts_with($trimmedContents, '[')
            ) {
                $post = json_decode($contents, true);

                if (is_array($post) && json_last_error() === JSON_ERROR_NONE) {
                    $this->originalPost += $post;
                }
            } elseif ($contents !== '') {
                $post = [];
                parse_str($contents, $post);
                $this->originalPost += $post;
            }
        }

        if (count($this->originalPost) !== 0) {
            $this->post = $this->parseInputItem(array: $this->originalPost);
        }

        /* Parse get requests */
        if (count($_FILES) !== 0) {
            $this->originalFile = $_FILES;
            $this->file = $this->parseFiles($this->originalFile);
        }
    }

    /**
     * @param array       $files     Array with files to parse.
     * @param string|null $parentKey Key from parent (used when parsing nested array).
     * @return array
     */
    public function parseFiles(array $files, ?string $parentKey = null): array
    {
        $list = [];

        foreach ($files as $key => $value) {

            // Parse multi dept file array
            if (isset($value['name']) === false && is_array($value) === true) {
                $list[$key] = $this->parseFiles($value, $key);
                continue;
            }

            // Handle array input
            if (is_array($value['name']) === false) {
                $values = ['index' => $parentKey ?? $key];

                try {
                    $list[$key] = File::createFromArray($values + $value);
                } catch (TypeException $e) {
                    //
                }
                continue;
            }

            $keys = [$key];
            $files = $this->rearrangeFile($value['name'], $keys, $value);

            if (isset($list[$key]) === true) {
                $list[$key][] = $files;
            } else {
                $list[$key] = $files;
            }

        }

        return $list;
    }

    /**
     * Rearrange multidimensional file object created by PHP.
     *
     * @param array $values
     * @param array $index
     * @param array|null $original
     * @return array|null
     */
    protected function rearrangeFile(array $values, array &$index, ?array $original = null): ?array
    {
        $originalIndex = $index[0];
        array_shift($index);

        $output = [];

        foreach ($values as $key => $value) {

            if (is_array($original['name'][$key]) === false) {

                try {

                    $file = File::createFromArray([
                        'index' => ($key === '' && $originalIndex !== '') ? $originalIndex : $key,
                        'name' => $original['name'][$key],
                        'error' => $original['error'][$key],
                        'tmp_name' => $original['tmp_name'][$key],
                        'type' => $original['type'][$key],
                        'size' => $original['size'][$key],
                    ]);

                    if (isset($output[$key]) === true) {
                        $output[$key][] = $file;
                        continue;
                    }

                    $output[$key] = $file;
                    continue;

                } catch (TypeException $e) {
                    //
                }
            }

            $index[] = $key;

            $files = $this->rearrangeFile($value, $index, $original);

            if (isset($output[$key]) === true) {
                $output[$key][] = $files;
            } else {
                $output[$key] = $files;
            }

        }

        return $output;
    }

    /**
     * Parse input item from array.
     */
    protected function parseInputItem(array $array): array
    {
        $list = [];

        foreach ($array as $key => $value) {

            // Handle array input
            if (is_array($value) === true) {
                $value = $this->parseInputItem($value);
            }

            $list[$key] = new Input($key, $value);
        }

        return $list;
    }

    /**
     * Find input object.
     *
     * @param string $index
     * @param string|array ...$methods
     * @return string|Input|array|File|null
     */
    public function find(string $index, ...$methods): string|Input|array|File|null
    {
        $element = null;

        if (count($methods) > 0) {
            $methods = count($methods) === 1 && is_array($methods[0])
            ? array_values($methods[0])
            : $methods;
        }

        if (count($methods) === 0 || in_array(Request::REQUEST_TYPE_GET, $methods, true) === true) {
            $element = $this->get($index);
        }

        if (
            ($element === null && count($methods) === 0)
            || (count($methods) !== 0
            && in_array(Request::REQUEST_TYPE_POST, $methods, true) === true)
        ) {
            $element = $this->post($index);
        }

        if (
            ($element === null && count($methods) === 0)
            || (count($methods) !== 0
            && in_array('file', $methods, true) === true)
        ) {
            $element = $this->file($index);
        }

        return $element;
    }

    protected function getValueFromArray(array $array): array
    {
        $output = [];
        /* @var $item Input */
        foreach ($array as $key => $item) {

            if ($item instanceof Item) {
                $item = $item->getValue();
            }

            $output[$key] = is_array($item) ? $this->getValueFromArray($item) : $item;
        }

        return $output;
    }

    /**
     * Get input element value matching index.
     *
     * @param string $index
     * @param string|null|mixed $defaultValue
     * @param array ...$methods
     * @return mixed
     */
    public function value(string $index, mixed $defaultValue = null, ...$methods): mixed
    {
        $input = $this->find($index, ...$methods);

        if ($input instanceof Item) {
            $input = $input->getValue();
        }

        /* Handle collection */
        if (is_array($input) === true) {
            $output = $this->getValueFromArray($input);

            return (count($output) === 0) ? $defaultValue : $output;
        }

        return ($input === null || trim($input) === '') ? $defaultValue : $input;
    }

    /**
     * Check if an input item exist. If an array is an
     * $index parameter the method returns true if all
     * elements exist.
     *
     * @param string|array $index
     * @param array ...$methods
     * @return bool
     */
    public function exists(string|array $index, ...$methods): bool
    {
        // Check array
        if (is_array($index) === true) {
            return array_all($index, fn($key) => $this->value($key, null, ...$methods) !== null);
        }

        return $this->value($index, null, ...$methods) !== null;
    }

    /**
     * Find post-value by index or return default value.
     *
     * @param string $index
     * @param string|null $defaultValue
     * @return Input|array|string|null
     */
    public function post(string $index, ?string $defaultValue = null): Input|array|string|null
    {
        return $this->post[$index] ?? $defaultValue;
    }

    /**
     * Find file by index or return default value.
     *
     * @param string $index
     * @param string|null $defaultValue
     * @return File|array|string|null
     */
    public function file(string $index, ?string $defaultValue = null): array|string|File|null
    {
        return $this->file[$index] ?? $defaultValue;
    }

    /**
     * Find parameter/query-string by index or return default value.
     *
     * @param string $index
     * @param string|null $defaultValue
     * @return Input|array|string|null
     */
    public function get(string $index, ?string $defaultValue = null): Input|array|string|null
    {
        return $this->get[$index] ?? $defaultValue;
    }

    /**
     * Get all get/post items.
     *
     * @param array $filter Only take items in filter.
     */
    public function all(array $filter = []): array
    {
        $output = $this->originalParams + $this->originalPost + $this->originalFile;
        $output = (count($filter) > 0) ? array_intersect_key($output, array_flip($filter)) : $output;

        foreach ($filter as $filterKey) {
            if (array_key_exists($filterKey, $output) === false) {
                $output[$filterKey] = null;
            }
        }

        return $output;
    }

    /**
     * Add GET parameter.
     */
    public function addGet(string $key, Input $input): void
    {
        $this->get[$key] = $input;
    }

    /**
     * Add POST parameter.
     */
    public function addPost(string $key, Input $input): void
    {
        $this->post[$key] = $input;
    }

    /**
     * Add FILE parameter.
     */
    public function addFile(string $key, File $file): void
    {
        $this->file[$key] = $file;
    }

    /**
     * Set original post variables.
     *
     * @param array $post
     * @return static $this
     */
    public function withOriginalPost(array $post): self
    {
        $new               = clone $this;
        $new->originalPost = $post;

        return $new;
    }

    /**
     * Set original get-variables.
     *
     * @param array $params
     * @return static $this
     */
    public function withOriginalParams(array $params): self
    {
        $new                 = clone $this;
        $new->originalParams = $params;

        return $new;
    }

    /**
     * Set original file posts variables.
     *
     * @param array $file
     * @return static $this
     */
    public function withOriginalFile(array $file): self
    {
        $new               = clone $this;
        $new->originalFile = $file;

        return $new;
    }
}
