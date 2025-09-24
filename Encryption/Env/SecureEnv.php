<?php

declare(strict_types=1);

namespace Qubus\Http\Encryption\Env;

use Defuse\Crypto\Exception\EnvironmentIsBrokenException;
use Defuse\Crypto\Exception\WrongKeyOrModifiedCiphertextException;
use Defuse\Crypto\Key;

class SecureEnv
{
    /**
     * @throws EnvironmentIsBrokenException
     * @throws WrongKeyOrModifiedCiphertextException
     */
    public function parse(string $input, Key $key): void
    {
        if ($decrypted = File::decrypt($input, $key)) {
            $parsed = Parser::parse($decrypted) ?? [];

            foreach ($parsed as $key => $value) {
                if (isset($parsed[$key]) && !empty($parsed[$key])) {
                    putenv($key . '=' . $value);
                }
            }
        }
    }
}
