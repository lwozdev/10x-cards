<?php

declare(strict_types=1);

namespace App\Application\Handler;

use App\Domain\Value\AiJobId;
use App\Domain\Value\CardPreview;
use App\Domain\Value\SuggestedSetName;

/**
 * Result returned by GenerateCardsHandler.
 *
 * Contains all data needed for the API response:
 * - job_id: For linking KPI when user saves the set
 * - suggestedName: AI-suggested name for the flashcard set
 * - cards: Generated flashcard previews (not persisted yet)
 * - generatedCount: Number of cards generated
 */
final readonly class GenerateCardsHandlerResult
{
    /**
     * @param CardPreview[] $cards
     */
    public function __construct(
        public AiJobId $jobId,
        public SuggestedSetName $suggestedName,
        public array $cards,
        public int $generatedCount,
    ) {
    }
}
