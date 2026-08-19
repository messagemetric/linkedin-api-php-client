<?php

namespace LinkedIn;

use Psr\Http\Message\ResponseInterface;

class AccessToken implements \JsonSerializable
{
    /**
     * Default-initialized so subclasses that skip parent::__construct() and
     * populate state through the setters still have initialized typed properties.
     */
    protected string $token = '';

    protected int $expiresAt = 0;

    public function __construct(string $token = '', int $expiresAt = 0)
    {
        $this->setToken($token);
        $this->setExpiresAt($expiresAt);
    }

    public function getToken(): string
    {
        return $this->token;
    }

    public function setToken(string $token): self
    {
        $this->token = $token;
        return $this;
    }

    /**
     * The number of seconds remaining before the token will expire.
     */
    public function getExpiresIn(): int
    {
        return $this->expiresAt - time();
    }

    /**
     * Set the token expiration from a number of seconds remaining.
     *
     * No declared return type: a documented subclass (ReviewWave's
     * AccessRefreshToken) overrides this without one, and a covariant return
     * type on the parent would make that override incompatible.
     */
    public function setExpiresIn(int $expiresIn)
    {
        $this->expiresAt = $expiresIn + time();
        return $this;
    }

    public function __toString(): string
    {
        return $this->token;
    }

    public function getExpiresAt(): int
    {
        return $this->expiresAt;
    }

    public function setExpiresAt(int $expiresAt): self
    {
        $this->expiresAt = $expiresAt;
        return $this;
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
