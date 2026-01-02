<?php

declare(strict_types=1);

namespace App\Application\Handler;

/**
 * Result returned after successfully creating a flashcard set.
 *
 * Contains the essential information needed to respond to the client
 * and confirm the set creation.
 */
final readonly class CreateSetResult
{
    /**
     * @param string $setId UUID of the newly created set
     * @param string $name Name of the created set
     * @param int $cardCount Number of cards created with the set
     */
    public function __construct(
        public string $setId,
        public string $name,
        public int $cardCount,
    ) {
    }
}
