<?php

declare(strict_types=1);

namespace App\Infrastructure\Integration\OpenRouter\Exception;

/**
 * Exception thrown when the request is invalid (HTTP 400).
 * Indicates malformed request payload or invalid parameters.
 * This error is NOT retryable.
 */
class OpenRouterInvalidRequestException extends OpenRouterApiException
{
}
