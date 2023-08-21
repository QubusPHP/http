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

use Psr\Http\Message\RequestInterface;
use Qubus\Exception\Data\TypeException;

use function array_flip;
use function array_intersect_key;
use function array_map;
use function array_shift;
use function count;
use function file_get_contents;
use function in_array;
use function is_array;
use function json_decode;
use function parse_str;
use function strpos;
use function strtoupper;
use function trim;

class Handler
{
    /** @var array $get */
    protected array $get = [];

    /** @var array $post */
    protected array $post = [];

    /** @var array $file */
    protected array $file = [];

    public function __construct(public readonly RequestInterface $request)
    {
        $this->parseInputs();
    }

    /**
     * Parse input values
     */
    public function parseInputs(): void
    {
        /* Parse get requests */
        if (count($_GET) !== 0) {
            $this->get = $this->parseInputItem($_GET);
        }

        /* Parse post requests */
        $postVars = $_POST;

        $verbs = array_map('strtoupper', ['put', 'patch', 'delete']);

        if (in_array($this->request->getMethod(), $verbs, false) === true) {
            parse_str(file_get_contents('php://input'), $postVars);
        }

        if (count($postVars) !== 0) {
            $this->post = $this->parseInputItem($postVars);
        }

        /* Parse get requests */
        if (count($_FILES) !== 0) {
            $this->file = $this->parseFiles();
        }
    }

    public function parseFiles(): array
    {
        $list = [];
        foreach ((array) $_FILES as $key => $value) {
            // Handle array input
            if (is_array($value['name']) === false) {
                $values['index'] = $key;
                try {
                    $list[$key] = File::createFromArray($values + $value);
                } catch (TypeException $e) {
                }
                continue;
            }

            $keys  = [$key];
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
     * @param array      $index
     * @param array|null $original
     */
    protected function rearrangeFile(array $values, &$index, $original): ?array
    {
        $originalIndex = $index[0];
        array_shift($index);

        $output = [];
        foreach ($values as $key => $value) {
            if (is_array($original['name'][$key]) === false) {
                try {
                    $file = File::createFromArray([
                        'index'    => empty($key) === true && empty($originalIndex) === false ? $originalIndex : $key,
                        'name'     => $original['name'][$key],
                        'error'    => $original['error'][$key],
                        'tmp_name' => $original['tmp_name'][$key],
                        'type'     => $original['type'][$key],
                        'size'     => $original['size'][$key],
                    ]);

                    if (isset($output[$key]) === true) {
                        $output[$key][] = $file;
                        continue;
                    }

                    $output[$key] = $file;
                    continue;
                } catch (TypeException $e) {
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
     * Parse input item from array
     */
    protected function parseInputItem(array $array): array
    {
        $list = [];
        foreach ($array as $key => $value) {
            // Handle array input
            if (is_array($value) === false) {
                $list[$key] = new Input($key, $value);
                continue;
            }

            $output     = $this->parseInputItem($value);
            $list[$key] = $output;
        }
        return $list;
    }

    /**
     * Find input object.
     *
     * @param string $index
     * @param array ...$methods
     * @return string|Input|array|File|null
     */
    public function find(string $index, ...$methods): string|Input|array|File|null
    {
        $element = null;

        if (count($methods) === 0 || in_array('get', $methods, true) === true) {
            $element = $this->get($index);
        }

        if (
            ($element === null && count($methods) === 0)
            || (count($methods) !== 0
            && in_array('post', $methods, true) === true)
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

    /**
     * Get input element value matching index
     *
     * @param string $index
     * @param string|null $defaultValue
     * @param array ...$methods
     * @return array|string|null
     */
    public function value(string $index, ?string $defaultValue = null, ...$methods): array|string|null
    {
        $input  = $this->find($index, ...$methods);
        $output = [];
        /* Handle collection */
        if (is_array($input) === true) {
            /** @var Input $item */
            foreach ($input as $item) {
                $output[] = $item->getValue();
            }
            return count($output) === 0 ? $defaultValue : $output;
        }
        return $input === null || ($input !== null
        && trim($input->getValue()) === '') ? $defaultValue : $input->getValue();
    }

    /**
     * Check if an input item exist.
     *
     * @param array ...$methods
     */
    public function exists(string $index, ...$methods): bool
    {
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
     * Get all get/post items
     *
     * @param array $filter Only take items in filter.
     */
    public function all(array $filter = []): array
    {
        $output = $_GET;
        if ($this->request->getMethod() === strtoupper('post')) {
            // Append POST data
            $output  += $_POST;
            $contents = file_get_contents('php://input');
            // Append any PHP input json
            if (strpos(trim($contents), '{') === 0) {
                $post = json_decode($contents, true);
                if ($post !== false) {
                    $output += $post;
                }
            }
        }
        return count($filter) > 0 ? array_intersect_key($output, array_flip($filter)) : $output;
    }

    /**
     * Add GET parameter
     */
    public function addGet(string $key, Input $input): void
    {
        $this->get[$key] = $input;
    }

    /**
     * Add POST parameter
     */
    public function addPost(string $key, Input $input): void
    {
        $this->post[$key] = $input;
    }

    /**
     * Add FILE parameter
     */
    public function addFile(string $key, File $file): void
    {
        $this->file[$key] = $file;
    }
}
