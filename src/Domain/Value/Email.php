<?php

declare(strict_types=1);

namespace App\Domain\Value;

use InvalidArgumentException;

final readonly class Email
{
    private function __construct(
        public string $value
    ) {
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException(sprintf('Invalid email format: %s', $value));
        }

        if (strlen($value) > 255) {
            throw new InvalidArgumentException('Email cannot exceed 255 characters');
        }
    }

    public static function fromString(string $email): self
    {
        return new self(strtolower(trim($email)));
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
