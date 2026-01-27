<?php

declare(strict_types=1);

namespace App\Application\Command;

use App\Domain\Value\SetName;
use App\Domain\Value\UserId;

/**
 * Command to update an existing flashcard set.
 *
 * Supports:
 * - Renaming the set
 * - Adding new cards
 * - Updating existing cards
 * - Deleting cards
 */
final readonly class UpdateSetCommand
{
    /**
     * @param string             $setId          ID of the set to update
     * @param UserId             $userId         Owner of the set (for authorization)
     * @param SetName            $name           New name of the set
     * @param UpdateSetCardDto[] $cards          Array of cards (new + existing)
     * @param string[]           $deletedCardIds Array of card IDs to delete
     */
    public function __construct(
        public string $setId,
        public UserId $userId,
        public SetName $name,
        public array $cards,
        public array $deletedCardIds = [],
    ) {
    }
}
