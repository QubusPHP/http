<?php

/**
 * Qubus\Http
 *
 * @link       https://github.com/QubusPHP/http
 * @copyright  2020 Joshua Parker <josh@joshuaparker.blog>
 * @license    https://opensource.org/licenses/mit-license.php MIT License
 *
 * @since      1.0.0
 */

declare(strict_types=1);

namespace Qubus\Tests\Http\Cookies\Validation;

use PHPUnit\Framework\TestCase;
use Qubus\Http\Cookies\Validation\Validation;

use function hash_hmac;

class ValidationTest extends TestCase
{
    public function testExtractsSuccessfully()
    {
        $algo = 'sha256';
        $key = 'E26m218TLqgJeY40ydCET10tMUD6qSlV';
        $nonce = 'Z6vsz6UqTtqYcPy4TRinVtb8ShsVvDvq';
        $value = 'hello world!';

        // We know how the hmac should be created.
        $hmac = hash_hmac($algo, $key, $nonce . $value);

        // We know what the signed value should be.
        $signedValue = $nonce . $value . '.' . $hmac;

        $validation = new Validation($key, $algo);

        $extractedValue = $validation->extract($signedValue);

        $this->assertEquals($value, $extractedValue);
    }

    public function testSignsSuccessfully()
    {
        $algo = 'sha256';
        $key = 'E26m218TLqgJeY40ydCET10tMUD6qSlV';
        $value = 'hello world!';

        $validation = new Validation($key, $algo);

        $signedValue = $validation->sign($value);

        $extractedValue = $validation->extract($signedValue);

        $this->assertEquals($value, $extractedValue);
    }
}
