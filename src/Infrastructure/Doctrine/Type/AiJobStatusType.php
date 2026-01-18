<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine\Type;

use App\Domain\Model\AiJobStatus;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;

/**
 * Doctrine DBAL Type for AiJobStatus enum.
 *
 * Maps PostgreSQL enum type 'ai_job_status' to PHP enum AiJobStatus.
 *
 * PostgreSQL enum definition:
 * CREATE TYPE ai_job_status AS ENUM ('queued', 'running', 'succeeded', 'failed');
 */
class AiJobStatusType extends Type
{
    public const NAME = 'ai_job_status';

    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return 'ai_job_status';
    }

    public function convertToPHPValue($value, AbstractPlatform $platform): ?AiJobStatus
    {
        if (null === $value) {
            return null;
        }

        return AiJobStatus::from($value);
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?string
    {
        if (null === $value) {
            return null;
        }

        if ($value instanceof AiJobStatus) {
            return $value->value;
        }

        throw new \InvalidArgumentException(sprintf('Expected %s, got %s', AiJobStatus::class, get_debug_type($value)));
    }

    public function getName(): string
    {
        return self::NAME;
    }

    public function requiresSQLCommentHint(AbstractPlatform $platform): bool
    {
        return true;
    }
}
