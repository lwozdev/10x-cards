<?php

declare(strict_types=1);

namespace App\Infrastructure\Integration\Ai\Exception;

/**
 * Exception thrown when AI service takes longer than allowed timeout (30s).
 *
 * This is a special case that maps to HTTP 504 Gateway Timeout
 * to inform the user that the request took too long.
 *
 * HTTP status: 504 Gateway Timeout
 */
class AiTimeoutException extends AiException
{
    public static function create(int $timeoutSeconds): self
    {
        return new self(sprintf('AI generation timed out after %d seconds', $timeoutSeconds));
    }
}
