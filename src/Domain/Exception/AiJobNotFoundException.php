<?php

declare(strict_types=1);

namespace App\Domain\Exception;

/**
 * Thrown when an AI job ID is provided but the job doesn't exist
 * or doesn't belong to the current user (RLS prevents access).
 */
final class AiJobNotFoundException extends \DomainException
{
    public static function forId(string $jobId): self
    {
        return new self(
            sprintf('AI job with ID "%s" not found or you do not have access to it.', $jobId)
        );
    }
}
