<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Model\AnalyticsEvent;
use App\Domain\Value\UserId;

interface AnalyticsEventRepositoryInterface
{
    public function save(AnalyticsEvent $event): void;

    /**
     * @return AnalyticsEvent[]
     */
    public function findByUser(UserId $userId, int $limit = 100): array;

    /**
     * @return AnalyticsEvent[]
     */
    public function findByEventType(string $eventType, int $limit = 100): array;
}
