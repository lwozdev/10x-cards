<?php

declare(strict_types=1);

namespace App\Application\Handler;

/**
 * Result of UpdateSetHandler operation.
 */
final readonly class UpdateSetResult
{
    public function __construct(
        public string $setId,
        public string $name,
        public int $cardCount,
    ) {
    }
}
