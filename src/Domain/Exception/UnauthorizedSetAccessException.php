<?php

declare(strict_types=1);

namespace App\Domain\Exception;

/**
 * Thrown when a user tries to access or modify a set they don't own.
 */
final class UnauthorizedSetAccessException extends \DomainException
{
    public static function forSet(string $setId): self
    {
        return new self(
            sprintf('You do not have permission to access or modify set "%s".', $setId)
        );
    }
}
