<?php

declare(strict_types=1);

namespace App\Domain\Model;

enum AiJobStatus: string
{
    case QUEUED = 'queued';
    case RUNNING = 'running';
    case SUCCEEDED = 'succeeded';
    case FAILED = 'failed';

    public function isTerminal(): bool
    {
        return in_array($this, [self::SUCCEEDED, self::FAILED], true);
    }

    public function isSuccessful(): bool
    {
        return $this === self::SUCCEEDED;
    }

    public function isFailed(): bool
    {
        return $this === self::FAILED;
    }
}
