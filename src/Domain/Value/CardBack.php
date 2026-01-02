<?php

declare(strict_types=1);

namespace App\Domain\Value;

use InvalidArgumentException;

final readonly class CardBack
{
    private function __construct(
        public string $value
    ) {
        $trimmed = trim($value);

        if ($trimmed === '') {
            throw new InvalidArgumentException('Card back cannot be empty');
        }

        if (mb_strlen($value) > 1000) {
            throw new InvalidArgumentException('Card back cannot exceed 1000 characters');
        }
    }

    public static function fromString(string $back): self
    {
        return new self($back);
    }

    public function toString(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
