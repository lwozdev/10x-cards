<?php

declare(strict_types=1);

namespace App\Infrastructure\Integration\OpenRouter\Exception;

/**
 * Exception thrown when network-level errors occur (connection refused, DNS failure, etc.).
 * This type of error is typically retryable.
 */
class OpenRouterNetworkException extends OpenRouterException
{
}
