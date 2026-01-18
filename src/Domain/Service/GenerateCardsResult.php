<?php

declare(strict_types=1);

namespace App\Domain\Service;

use App\Domain\Value\CardPreview;
use App\Domain\Value\SuggestedSetName;

/**
 * Result DTO returned by AI card generator.
 *
 * Contains all data returned from the AI service:
 * - Generated flashcards (previews, not persisted yet)
 * - Suggested name for the flashcard set
 * - Metadata about AI model and token usage
 */
final readonly class GenerateCardsResult
{
    /**
     * @param CardPreview[] $cards
     */
    public function __construct(
        public array $cards,
        public SuggestedSetName $suggestedName,
        public string $modelName,
        public int $tokensIn,
        public int $tokensOut,
    ) {
    }

    public function generatedCount(): int
    {
        return count($this->cards);
    }
}
