<?php

namespace LinkedIn;

use Psr\Http\Message\ResponseInterface;

class AccessToken implements \JsonSerializable
{
    public function __construct(
        public readonly string $token = '',
        public readonly int $expiresAt = 0,
    ) {}

    public function getToken(): string
    {
        return $this->token;
    }

    /**
     * The number of seconds remaining before the token will expire.
     */
    public function getExpiresIn(): int
    {
        return $this->expiresAt - time();
    }

    public function __toString(): string
    {
        return $this->token;
    }

    public function getExpiresAt(): int
    {
        return $this->expiresAt;
    }

    /**
     * Convert API response into AccessToken.
     */
    public static function fromResponse(ResponseInterface $response): static
    {
        return static::fromResponseArray(
            Client::responseToArray($response)
        );
    }

    /**
     * Instantiate access token from response array.
     *
     * @throws \InvalidArgumentException
     */
    public static function fromResponseArray(array $responseArray): static
    {
        if (!isset($responseArray['access_token'])) {
            throw new \InvalidArgumentException(
                'Access token is not available'
            );
        }
        if (!isset($responseArray['expires_in'])) {
            throw new \InvalidArgumentException(
                'Access token expiration date is not specified'
            );
        }
        return new static(
            token: $responseArray['access_token'],
            expiresAt: $responseArray['expires_in'] + time(),
        );
    }

    public function jsonSerialize(): array
    {
        return [
            'token' => $this->token,
            'expiresAt' => $this->expiresAt,
        ];
    }
}
