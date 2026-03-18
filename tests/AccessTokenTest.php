<?php

namespace LinkedIn;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class AccessTokenTest extends TestCase
{
    #[DataProvider('getValidResponseTestTable')]
    public function testConstructorFromResponseArray(AccessToken $expectedToken, array $response): void
    {
        $token = AccessToken::fromResponseArray($response);
        $this->assertEquals($expectedToken->getToken(), $token->getToken());
    }

    public static function getValidResponseTestTable(): array
    {
        return [
            [
                'expectedToken' => new AccessToken('test', 0),
                'response' => [
                    'access_token' => 'test',
                    'expires_in' => 0,
                ],
            ]
        ];
    }

    #[DataProvider('getInvalidResponseTestTable')]
    public function testConstructorFromResponseArrayWithException(
        string $exceptionClass,
        string $exceptionMessage,
        array $response,
    ): void {
        $this->expectException($exceptionClass);
        $this->expectExceptionMessage($exceptionMessage);
        AccessToken::fromResponseArray($response);
    }

    public static function getInvalidResponseTestTable(): array
    {
        return [
            [
                'exceptionClass' => \InvalidArgumentException::class,
                'exceptionMessage' => 'Access token is not available',
                'response' => [],
            ],
            [
                'exceptionClass' => \InvalidArgumentException::class,
                'exceptionMessage' => 'Access token is not available',
                'response' => [
                    'access_token' => null,
                ],
            ],
            [
                'exceptionClass' => \InvalidArgumentException::class,
                'exceptionMessage' => 'Access token is not available',
                'response' => [
                    'expires_in' => 1,
                ],
            ],
            [
                'exceptionClass' => \InvalidArgumentException::class,
                'exceptionMessage' => 'Access token expiration date is not specified',
                'response' => [
                    'access_token' => 'hello',
                ],
            ],
            [
                'exceptionClass' => \InvalidArgumentException::class,
                'exceptionMessage' => 'Access token expiration date is not specified',
                'response' => [
                    'access_token' => 'hello',
                    'expires_in' => null,
                ],
            ],
        ];
    }

    public function testToString(): void
    {
        $token = new AccessToken('hello', 1);
        $this->assertEquals('hello', (string) $token);
    }

    public function testJsonSerialize(): void
    {
        $token = new AccessToken('hello', 1);
        $this->assertEquals('{"token":"hello","expiresAt":1}', json_encode($token));
    }
}
