<?php

namespace TwigYard\Component;

class CsrfTokenGenerator
{
    public function generateToken(): string
    {
        $bytes = openssl_random_pseudo_bytes(30);
        if (!$bytes) {
            throw new \Exception('Bytes could not be generated');
        }

        return base64_encode($bytes);
    }
}
