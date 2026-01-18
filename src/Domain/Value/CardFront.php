<?php

declare(strict_types=1);

namespace App\Domain\Value;

final readonly class CardFront
{
    private function __construct(
        public string $value,
    ) {
        $trimmed = trim($value);

        if ('' === $trimmed) {
            throw new \InvalidArgumentException('Card front cannot be empty');
        }

        if (mb_strlen($value) > 1000) {
            throw new \InvalidArgumentException('Card front cannot exceed 1000 characters');
        }
    }

    public static function fromString(string $front): self
    {
        return new self($front);
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
