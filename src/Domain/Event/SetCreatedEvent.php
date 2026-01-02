<?php

declare(strict_types=1);

namespace App\Domain\Event;

/**
 * Event dispatched when a new flashcard set is successfully created.
 *
 * Used for:
 * - Analytics tracking (set_created event)
 * - KPI metrics (AI card acceptance rates, edit rates)
 * - Audit logging
 */
final readonly class SetCreatedEvent
{
    /**
     * @param string $setId UUID of the newly created set
     * @param string $userId UUID of the user who created the set
     * @param int $totalCardCount Total number of cards created with the set
     * @param int $aiCardCount Number of AI-generated cards in the set
     * @param int $editedAiCardCount Number of AI cards that were edited before saving
     * @param string|null $jobId Optional AI job ID that generated the cards
     */
    public function __construct(
        public string $setId,
        public string $userId,
        public int $totalCardCount,
        public int $aiCardCount,
        public int $editedAiCardCount,
        public ?string $jobId = null,
    ) {
    }

    /**
     * Calculate the number of manual cards in the set
     */
    public function getManualCardCount(): int
    {
        return $this->totalCardCount - $this->aiCardCount;
    }

    /**
     * Calculate the percentage of AI cards that were edited
     * Returns 0.0 if no AI cards
     */
    public function getAiEditRate(): float
    {
        if ($this->aiCardCount === 0) {
            return 0.0;
        }

        return $this->editedAiCardCount / $this->aiCardCount;
    }
}
