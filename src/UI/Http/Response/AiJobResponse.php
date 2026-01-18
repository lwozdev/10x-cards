<?php

declare(strict_types=1);

namespace App\UI\Http\Response;

/**
 * Response DTO for AI job creation.
 *
 * Returns the job ID and status to the client immediately
 * after enqueueing the AI generation task.
 */
final readonly class AiJobResponse
{
    public function __construct(
        public string $jobId,
        public string $status,
    ) {
    }

    /**
     * Convert to array for JSON serialization.
     *
     * @return array{job_id: string, status: string}
     */
    public function toArray(): array
    {
        return [
            'job_id' => $this->jobId,
            'status' => $this->status,
        ];
    }
}
