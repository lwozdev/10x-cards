<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Model\AiJob;
use App\Domain\Model\AiJobStatus;
use App\Domain\Value\UserId;

interface AiJobRepositoryInterface
{
    public function findById(string $id): ?AiJob;

    /**
     * @return AiJob[]
     */
    public function findByUser(UserId $userId, int $limit = 50): array;

    /**
     * @return AiJob[]
     */
    public function findByStatus(AiJobStatus $status, int $limit = 100): array;

    public function save(AiJob $job): void;

    public function countFailedByUser(UserId $userId): int;
}
