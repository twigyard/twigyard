<?php

namespace TwigYard\Component;

class CsrfTokenGenerator
{
    /**
     * @return string
     */
    public function generateToken(): string
    {
        return base64_encode(openssl_random_pseudo_bytes(30));
    }
}
