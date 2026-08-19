<?php

namespace LinkedIn;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ClientTest extends TestCase
{
    public Client $client;

    protected function setUp(): void
    {
        $this->client = new Client(
            getenv('LINKEDIN_CLIENT_ID') ?: '',
            getenv('LINKEDIN_CLIENT_SECRET') ?: '',
        );
    }

    public function testGetLoginUrl(): void
    {
        $actual = $this->client->getLoginUrl();
        $this->assertNotEmpty($actual);
    }

    #[DataProvider('getSetAccessTokenTestTable')]
    public function testSetAccessToken(
        AccessToken|string $token,
        ?AccessToken $expectedToken,
    ): void {
        $client = new Client();
        $client->setAccessToken($token);

        if ($expectedToken !== null) {
            $this->assertEquals(
                $expectedToken->getToken(),
                $client->getAccessToken()->getToken()
            );
        }
    }

    public static function getSetAccessTokenTestTable(): array
    {
        return [
            'string token' => [
                'token' => 'test token',
                'expectedToken' => new AccessToken('test token'),
            ],
            'AccessToken object' => [
                'token' => new AccessToken('hello world'),
                'expectedToken' => new AccessToken('hello world'),
            ],
        ];
    }

    #[DataProvider('getSetAccessTokenExceptionTestTable')]
    public function testSetAccessTokenWithException(
        mixed $token,
        string $exceptionClass,
    ): void {
        $this->expectException($exceptionClass);
        $client = new Client();
        $client->setAccessToken($token);
    }

    public static function getSetAccessTokenExceptionTestTable(): array
    {
        return [
            'null throws TypeError' => [
                'token' => null,
                'exceptionClass' => \TypeError::class,
            ],
            'object throws TypeError' => [
                'token' => new \StdClass(),
                'exceptionClass' => \TypeError::class,
            ],
        ];
    }
}
