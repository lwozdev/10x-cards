<?php

declare(strict_types=1);

namespace App\Infrastructure\Integration\OpenRouter\Exception;

/**
 * Exception thrown when OpenRouter server returns an error (HTTP 500+).
 * This error is typically retryable as it indicates a temporary server issue.
 */
class OpenRouterServerException extends OpenRouterApiException
{
}
