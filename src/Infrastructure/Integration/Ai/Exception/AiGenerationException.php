<?php

declare(strict_types=1);

namespace App\Infrastructure\Integration\Ai\Exception;

/**
 * Exception thrown when AI service returns an error during generation.
 *
 * Examples:
 * - AI service returns 4xx/5xx error
 * - AI response is malformed or unparseable
 * - AI returns invalid data (e.g., empty cards)
 *
 * HTTP status: 500 Internal Server Error
 */
class AiGenerationException extends AiException
{
    public static function invalidResponse(string $reason): self
    {
        return new self(sprintf('AI returned invalid response: %s', $reason));
    }

    public static function serviceError(int $statusCode, string $message): self
    {
        return new self(sprintf('AI service error (HTTP %d): %s', $statusCode, $message));
    }

    public static function emptyCards(): self
    {
        return new self('AI generated no flashcards');
    }
}
