<?php

declare(strict_types=1);

namespace App\UI\Http\Response;

/**
 * Response DTO for POST /api/generate endpoint.
 *
 * JSON structure:
 * {
 *   "job_id": "uuid",
 *   "suggested_name": "Subject - Topic",
 *   "cards": [
 *     {"front": "Question?", "back": "Answer."}
 *   ],
 *   "generated_count": 15
 * }
 */
final class GenerateCardsResponse
{
    /**
     * @param CardPreviewDto[] $cards
     */
    public function __construct(
        public readonly string $jobId,
        public readonly string $suggestedName,
        public readonly array $cards,
        public readonly int $generatedCount,
    ) {
    }
}
