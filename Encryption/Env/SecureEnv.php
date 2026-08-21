<?php

declare(strict_types=1);

namespace Qubus\Http\Encryption\Env;

use Defuse\Crypto\Exception\BadFormatException;
use Defuse\Crypto\Exception\EnvironmentIsBrokenException;
use Defuse\Crypto\Exception\WrongKeyOrModifiedCiphertextException;
use Defuse\Crypto\Key;
use RuntimeException;

use function file_get_contents;

class SecureEnv
{
    /**
     * @throws EnvironmentIsBrokenException
     * @throws WrongKeyOrModifiedCiphertextException
     * @throws BadFormatException
     */
    public static function parse(string $inputFile, string $keyFile): void
    {
        $keyString = file_get_contents($keyFile);
        if ($keyString === false) {
            throw new RuntimeException('Unable to read the environment encryption key.');
        }

        if ($decrypted = File::decrypt($inputFile, Key::loadFromAsciiSafeString($keyString))) {
            $parsed = Parser::parse($decrypted);

            foreach ($parsed as $key => $value) {
                if (isset($parsed[$key]) && !empty($parsed[$key])) {
                    putenv(assignment: $key . '=' . $value);
                }
            }
        }
    }
}
