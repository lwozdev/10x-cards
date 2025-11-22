<?php

declare(strict_types=1);

namespace App\Infrastructure\Integration\OpenRouter\Exception;

/**
 * Exception thrown when authentication fails (HTTP 401).
 * Indicates invalid or missing API key.
 * This error is NOT retryable.
 */
class OpenRouterAuthenticationException extends OpenRouterApiException
{
}
