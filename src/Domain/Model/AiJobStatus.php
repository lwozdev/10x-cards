<?php

declare(strict_types=1);

namespace App\Domain\Model;

/**
 * AI Job Status - synchronous generation (no queuing)
 *
 * Jobs complete immediately:
 * - SUCCEEDED: AI successfully generated cards
 * - FAILED: AI generation failed with error
 */
enum AiJobStatus: string
{
    case SUCCEEDED = 'succeeded';
    case FAILED = 'failed';

    public function isSuccessful(): bool
    {
        return $this === self::SUCCEEDED;
    }

    public function isFailed(): bool
    {
        return $this === self::FAILED;
    }
}
