<?php

namespace TwigYard\Component;

use Psr\Http\Message\ResponseInterface;

class HttpRequestSender
{
    public function sendRequest(string $method, string $url, array $data = [], array $headers = []): ResponseInterface
    {
        $client = new \GuzzleHttp\Client();

        return $client->request(
            $method,
            $url,
            [
                'headers' => $headers,
                'json' => $data,
            ]
        );
    }
}
