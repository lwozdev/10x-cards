<?php

declare(strict_types=1);

namespace App\Domain\Value;

use InvalidArgumentException;

/**
 * Represents source text for AI flashcard generation.
 *
 * Enforces business rules:
 * - Minimum 1000 characters
 * - Maximum 10000 characters
 * - Cannot be empty after trimming whitespace
 */
final readonly class SourceText
{
    private const MIN_LENGTH = 1000;
    private const MAX_LENGTH = 10000;

    private function __construct(
        public string $value
    ) {
        $this->validate($value);
    }

    public static function fromString(string $text): self
    {
        return new self($text);
    }

    public function toString(): string
    {
        return $this->value;
    }

    public function length(): int
    {
        return mb_strlen($this->value, 'UTF-8');
    }

    private function validate(string $text): void
    {
        $trimmed = trim($text);

        if ($trimmed === '') {
            throw new InvalidArgumentException('Source text cannot be empty');
        }

        $length = mb_strlen($trimmed, 'UTF-8');

        if ($length < self::MIN_LENGTH) {
            throw new InvalidArgumentException(
                sprintf(
                    'Source text must be at least %d characters long, got %d',
                    self::MIN_LENGTH,
                    $length
                )
            );
        }

        if ($length > self::MAX_LENGTH) {
            throw new InvalidArgumentException(
                sprintf(
                    'Source text must not exceed %d characters, got %d',
                    self::MAX_LENGTH,
                    $length
                )
            );
        }
    }
}
