<?php

declare(strict_types=1);

namespace App\Infrastructure\Integration\OpenRouter\Exception;

/**
 * Exception thrown when a request to OpenRouter API times out.
 * This error is typically retryable.
 */
class OpenRouterTimeoutException extends OpenRouterException
{
}
