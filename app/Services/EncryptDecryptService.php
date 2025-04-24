<?php

declare(strict_types=1);

namespace App\Services;

class EncryptDecryptService
{
    private string $method = 'AES-128-ECB';

    public function encrypt(string $data, string $key): string
    {
        $encrypted = openssl_encrypt($data, $this->method, $key, OPENSSL_RAW_DATA);

        return bin2hex($encrypted);
    }

    public function decrypt(string $encryptData, string $key): false|string
    {
        $encrypted = hex2bin($encryptData);

        return openssl_decrypt($encrypted, $this->method, $key, OPENSSL_RAW_DATA);
    }
}
