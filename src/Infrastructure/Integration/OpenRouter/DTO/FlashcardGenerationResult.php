<?php

declare(strict_types=1);

namespace App\Infrastructure\Integration\OpenRouter\DTO;

/**
 * Result DTO for flashcard generation with metadata.
 * Contains both the generated flashcards and metadata from the API.
 */
final readonly class FlashcardGenerationResult
{
    /**
     * @param Flashcard[] $flashcards
     */
    public function __construct(
        public array $flashcards,
        public string $suggestedName,
        public string $modelName,
        public int $promptTokens,
        public int $completionTokens,
        public int $totalTokens,
    ) {
    }
}
