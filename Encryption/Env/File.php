<?php

declare(strict_types=1);

namespace Qubus\Http\Encryption\Env;

use Defuse\Crypto\Crypto;
use Defuse\Crypto\Exception\EnvironmentIsBrokenException;
use Defuse\Crypto\Exception\WrongKeyOrModifiedCiphertextException;
use Defuse\Crypto\Key;

use function file_get_contents;
use function file_put_contents;

class File
{
    /**
     * Encrypts the input file's content,
     * and writes the encrypted data to
     * an output file.
     *
     * @throws EnvironmentIsBrokenException
     */
    public static function encrypt(string $input, $output, Key $key): string
    {
        $inputFile = file_get_contents($input);

        $encryptedText = Crypto::encrypt($inputFile, $key);
        file_put_contents($output, $encryptedText);

        return $encryptedText;
    }

    /**
     * Decrypts the data from the input file
     * and returns the decrypted data.
     *
     * @throws WrongKeyOrModifiedCiphertextException
     * @throws EnvironmentIsBrokenException
     */
    public static function decrypt(string $input, Key $key): string
    {
        $inputFile = file_get_contents($input);

        return Crypto::decrypt($inputFile, $key);
    }
}
