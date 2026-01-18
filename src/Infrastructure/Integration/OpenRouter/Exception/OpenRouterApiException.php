<?php

declare(strict_types=1);

namespace App\Infrastructure\Integration\OpenRouter\Exception;

/**
 * Base exception for API-level errors returned by OpenRouter.
 * Contains HTTP status code and optional API response payload.
 */
class OpenRouterApiException extends OpenRouterException
{
    public function __construct(
        string $message,
        private readonly int $httpStatusCode,
        private readonly ?array $apiResponse = null,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }

    public function getHttpStatusCode(): int
    {
        return $this->httpStatusCode;
    }

    public function getApiResponse(): ?array
    {
        return $this->apiResponse;
    }
}
