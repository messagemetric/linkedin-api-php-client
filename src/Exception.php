<?php

namespace LinkedIn;

use GuzzleHttp\Exception\RequestException;

class Exception extends \Exception
{
    protected string $description;

    public function __construct(
        string $message = '',
        int $code = 0,
        ?\Throwable $previousException = null,
        string $description = '',
    ) {
        parent::__construct($message, $code, $previousException);
        $this->description = $description;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public static function fromRequestException(RequestException $exception): static
    {
        return new static(
            $exception->getMessage(),
            $exception->getCode(),
            $exception,
            static::extractErrorDescription($exception) ?? '',
        );
    }

    protected static function extractErrorDescription(RequestException $exception): ?string
    {
        $response = $exception->getResponse();
        if (!$response) {
            return null;
        }

        try {
            $json = Client::responseToArray($response);
        } catch (\JsonException) {
            return null;
        }
        if (isset($json['error_description'])) {
            return $json['error_description'];
        }
        if (isset($json['message'])) {
            return $json['message'];
        }
        return null;
    }
}
