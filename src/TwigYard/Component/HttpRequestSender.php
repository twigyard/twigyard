<?php

namespace TwigYard\Component;

use GuzzleHttp\Client;
use Psr\Http\Message\ResponseInterface;

class HttpRequestSender
{
    public function sendJsonRequest(string $method, string $url, array $data = [], array $headers = []): ResponseInterface
    {
        $client = new Client();

        return $client->request(
            $method,
            $url,
            [
                'headers' => $headers,
                'json' => $data,
            ]
        );
    }

    public function sendUrlencodedRequest(string $url, array $data = [], array $headers = []): ResponseInterface
    {
        $client = new Client();

        return $client->request(
            'POST', // no other method is supported
            $url,
            [
                'headers' => $headers,
                'form_params' => $data,
            ]
        );
    }
}
