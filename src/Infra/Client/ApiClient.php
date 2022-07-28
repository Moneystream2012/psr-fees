<?php

namespace App\Infra\Client;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class ApiClient
{
    public function __construct() {
        echo "ApiClient::__construct";
    }

    /**
     * @param string $uri
     * @param array $headers
     * @return string
     * @throws GuzzleException
     */
    public static function getResourceByUri(string $uri, array $headers = []): string
    {
        $client = new Client(['base_uri' => $uri]);
        $response = $client->request('GET', $uri, ['headers' => $headers]);
        return $response->getBody()->getContents();
    }

    /**
     * @param string $basePath
     * @param string $path
     * @return string|false
     */
    public static function getResourceByPath(string $basePath, string $path): bool|string
    {
        return @file_get_contents($basePath . '/'. $path);
    }
}