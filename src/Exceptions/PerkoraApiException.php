<?php

declare(strict_types=1);

namespace Perkora\SDK\Exceptions;

use RuntimeException;

final class PerkoraApiException extends RuntimeException
{
    public function __construct(
        string $message,
        private readonly int $httpStatus = 0,
        private readonly array $response = [],
        private readonly string $rawBody = ''
    ) {
        parent::__construct($message);
    }

    public function getHttpStatus(): int
    {
        return $this->httpStatus;
    }

    public function getResponse(): array
    {
        return $this->response;
    }

    public function getRawBody(): string
    {
        return $this->rawBody;
    }
}
