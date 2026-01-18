<?php

declare(strict_types=1);

namespace App\Infrastructure\Integration\OpenRouter\Exception;

/**
 * Exception thrown when rate limit is exceeded (HTTP 429).
 * Contains retry_after information if provided by the API.
 */
class OpenRouterRateLimitException extends OpenRouterApiException
{
    public function __construct(
        string $message,
        int $httpStatusCode,
        ?array $apiResponse = null,
        private readonly ?int $retryAfterSeconds = null,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $httpStatusCode, $apiResponse, $previous);
    }

    public function getRetryAfterSeconds(): ?int
    {
        return $this->retryAfterSeconds;
    }
}
