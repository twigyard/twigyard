<?php

namespace TwigYard\Component;

use TwigYard\Exception\CannotAccessRemoteSourceException;

class CurlDownloader
{
    const HTTP_STATUS_OK = 200;

    /**
     * @throws CannotAccessRemoteSourceException
     */
    public function loadRemoteContent(string $url): string
    {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_URL => $url,
        ]);
        $data = curl_exec($ch);
        $error = curl_error($ch);
        $httpStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if (self::HTTP_STATUS_OK !== $httpStatus) {
            throw new CannotAccessRemoteSourceException(sprintf('Unexpected status of http response %d', $httpStatus));
        }
        if ($error !== '') {
            throw new CannotAccessRemoteSourceException($error);
        }

        return (string) $data; // cast to string to satisfy phpstan
    }
}
