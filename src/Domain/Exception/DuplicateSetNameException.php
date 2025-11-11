<?php

declare(strict_types=1);

namespace App\Domain\Exception;

use DomainException;

/**
 * Thrown when attempting to create a set with a name that already exists
 * for the same user (case-insensitive).
 */
final class DuplicateSetNameException extends DomainException
{
    public static function forName(string $name): self
    {
        return new self(
            sprintf('A set with the name "%s" already exists. Please choose a different name.', $name)
        );
    }
}
