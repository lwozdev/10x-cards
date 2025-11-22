<?php

declare(strict_types=1);

namespace App\Infrastructure\Integration\OpenRouter\Exception;

/**
 * Exception thrown when response parsing fails.
 * Indicates unexpected response format from the API.
 * This error is NOT retryable.
 */
class OpenRouterParseException extends OpenRouterException
{
}
