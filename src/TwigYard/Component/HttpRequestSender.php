<?php

namespace TwigYard\Component;

use Psr\Http\Message\ResponseInterface;

class HttpRequestSender
{
    /**
     * @param string $method
     * @param string $url
     * @param array $data
     * @param array $headers
     * @return ResponseInterface
     */
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
