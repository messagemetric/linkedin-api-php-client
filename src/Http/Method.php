<?php

namespace LinkedIn\Http;

enum Method: string
{
    case CONNECT = 'CONNECT';
    case GET = 'GET';
    case HEAD = 'HEAD';
    case POST = 'POST';
    case PUT = 'PUT';
    case PATCH = 'PATCH';
    case OPTIONS = 'OPTIONS';
    case DELETE = 'DELETE';
    case TRACE = 'TRACE';

    public static function isMethodSupported(self $method): void
    {
        match ($method) {
            self::GET, self::POST, self::DELETE => null,
            default => throw new \InvalidArgumentException('The method is not correct'),
        };
    }
}
