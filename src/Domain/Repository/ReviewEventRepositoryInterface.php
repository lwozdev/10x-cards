<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Model\ReviewEvent;
use App\Domain\Value\UserId;

interface ReviewEventRepositoryInterface
{
    public function save(ReviewEvent $event): void;

    /**
     * @return ReviewEvent[]
     */
    public function findRecentByUser(UserId $userId, int $limit = 100): array;

    public function countByUserAndCard(UserId $userId, string $cardId): int;
}
