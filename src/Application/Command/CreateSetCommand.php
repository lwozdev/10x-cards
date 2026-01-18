<?php

declare(strict_types=1);

namespace App\Application\Command;

use App\Domain\Value\AiJobId;
use App\Domain\Value\SetName;
use App\Domain\Value\UserId;

/**
 * Command to create a new flashcard set with optional cards.
 *
 * This command supports two use cases:
 * 1. Creating an empty set for manual card addition later
 * 2. Creating a set with AI-generated cards (or manually created cards from frontend)
 *
 * When job_id is provided, it links the created set to an AI generation job for KPI tracking.
 */
final readonly class CreateSetCommand
{
    /**
     * @param UserId             $userId Owner of the set
     * @param SetName            $name   Name of the set (unique per user, case-insensitive)
     * @param CreateSetCardDto[] $cards  Array of cards to create with the set
     * @param AiJobId|null       $jobId  Optional AI job ID for KPI linkage
     */
    public function __construct(
        public UserId $userId,
        public SetName $name,
        public array $cards,
        public ?AiJobId $jobId = null,
    ) {
    }
}
