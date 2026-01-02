<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine\Repository;

use App\Domain\Model\AnalyticsEvent;
use App\Domain\Repository\AnalyticsEventRepositoryInterface;
use App\Domain\Value\UserId;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AnalyticsEvent>
 */
class DoctrineAnalyticsEventRepository extends ServiceEntityRepository implements AnalyticsEventRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AnalyticsEvent::class);
    }

    public function save(AnalyticsEvent $event): void
    {
        $this->getEntityManager()->persist($event);
        $this->getEntityManager()->flush();
    }

    public function findByUser(UserId $userId, int $limit = 100): array
    {
        return $this->createQueryBuilder('ae')
            ->where('ae.userId = :userId')
            ->setParameter('userId', $userId->toString())
            ->orderBy('ae.occurredAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findByEventType(string $eventType, int $limit = 100): array
    {
        return $this->createQueryBuilder('ae')
            ->where('ae.eventType = :eventType')
            ->setParameter('eventType', $eventType)
            ->orderBy('ae.occurredAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
