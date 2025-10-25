<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Model\ReviewState;
use App\Domain\Value\UserId;
use DateTimeImmutable;

interface ReviewStateRepositoryInterface
{
    public function findByUserAndCard(UserId $userId, string $cardId): ?ReviewState;

    /**
     * Find cards due for review (due_at <= now)
     *
     * @return ReviewState[]
     */
    public function findDueForUser(UserId $userId, DateTimeImmutable $now, int $limit = 20): array;

    public function save(ReviewState $state): void;

    public function countDueForUser(UserId $userId, DateTimeImmutable $now): int;
}
