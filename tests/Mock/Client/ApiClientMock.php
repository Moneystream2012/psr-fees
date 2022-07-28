<?php

namespace App\Tests\Mock\Client;

class ApiClientMock
{
    /**
     * @return string
     */
    public static function getResourceByUri(): string
    {
        $localFile = sprintf(
            '%s/%s.json',
            dirname(__DIR__) . '/resources/api/rates/',
            'default'
        );

        return @file_get_contents($localFile);
    }

    /**
     * @param string $path
     * @return bool|string
     */
    public static function getResourceByPath(string $path): bool|string
    {
        $localFile = sprintf(
            '%s/%s.json',
            dirname(__DIR__) . '/resources/api/bin-list',
            $path
        );

        return @file_get_contents($localFile);
    }
}