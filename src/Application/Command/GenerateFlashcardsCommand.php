<?php

declare(strict_types=1);

namespace App\Application\Command;

use App\Domain\Value\UserId;

/**
 * Command to initiate AI-powered flashcard generation from source text.
 *
 * This command represents the user's intent to generate flashcards
 * using AI. It creates an AiJob record with status "queued" which
 * will be processed asynchronously.
 */
final readonly class GenerateFlashcardsCommand
{
    public function __construct(
        public UserId $userId,
        public string $sourceText,
    ) {
    }
}
