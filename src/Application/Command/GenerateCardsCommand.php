<?php

declare(strict_types=1);

namespace App\Application\Command;

use App\Domain\Value\SourceText;
use App\Domain\Value\UserId;

/**
 * Command to generate flashcards from source text using AI.
 *
 * This is a synchronous command - the handler will:
 * 1. Call AI service to generate cards (blocking, max 30s)
 * 2. Create AiJob record with status SUCCEEDED or FAILED
 * 3. Return generated cards to the user immediately
 *
 * Flow:
 * - User provides source text (1000-10000 chars)
 * - AI generates flashcards synchronously
 * - Frontend receives cards and manages them locally
 * - User can edit/delete cards before saving via POST /api/sets
 */
final readonly class GenerateCardsCommand
{
    public function __construct(
        public SourceText $sourceText,
        public UserId $userId,
    ) {
    }
}
