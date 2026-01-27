<?php

declare(strict_types=1);

namespace App\Domain\Exception;

/**
 * Thrown when a set ID is provided but the set doesn't exist or has been deleted.
 */
final class SetNotFoundException extends \DomainException
{
    public static function forId(string $setId): self
    {
        return new self(
            sprintf('Flashcard set with ID "%s" not found.', $setId)
        );
    }
}
