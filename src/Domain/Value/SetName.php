<?php

declare(strict_types=1);

namespace App\Domain\Value;

use InvalidArgumentException;

final readonly class SetName
{
    private function __construct(
        public string $value
    ) {
        $trimmed = trim($value);

        if ($trimmed === '') {
            throw new InvalidArgumentException('Set name cannot be empty');
        }

        if (mb_strlen($trimmed) > 255) {
            throw new InvalidArgumentException('Set name cannot exceed 255 characters');
        }
    }

    public static function fromString(string $name): self
    {
        return new self($name);
    }

    public function toString(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        // Case-insensitive comparison (similar to CITEXT)
        return mb_strtolower($this->value) === mb_strtolower($other->value);
    }
}
