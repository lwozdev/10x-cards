<?php

declare(strict_types=1);

namespace App\Domain\Value;

use InvalidArgumentException;

final readonly class UserId
{
    private function __construct(
        public string $value
    ) {
        if (!$this->isValidUuid($value)) {
            throw new InvalidArgumentException('Invalid UUID format');
        }
    }

    public static function fromString(string $uuid): self
    {
        return new self($uuid);
    }

    public static function generate(): self
    {
        // Generate UUID v4
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // Version 4
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // Variant

        return new self(vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4)));
    }

    public function toString(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    private function isValidUuid(string $uuid): bool
    {
        return (bool) preg_match(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
            $uuid
        );
    }
}
