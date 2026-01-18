<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine\Repository;

use App\Domain\Model\ReviewEvent;
use App\Domain\Repository\ReviewEventRepositoryInterface;
use App\Domain\Value\UserId;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ReviewEvent>
 */
class DoctrineReviewEventRepository extends ServiceEntityRepository implements ReviewEventRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ReviewEvent::class);
    }

    public function save(ReviewEvent $event): void
    {
        $this->getEntityManager()->persist($event);
        $this->getEntityManager()->flush();
    }

    public function findRecentByUser(UserId $userId, int $limit = 100): array
    {
        return $this->createQueryBuilder('re')
            ->where('re.userId = :userId')
            ->setParameter('userId', $userId->toString())
            ->orderBy('re.answeredAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function countByUserAndCard(UserId $userId, string $cardId): int
    {
        return (int) $this->createQueryBuilder('re')
            ->select('COUNT(re.id)')
            ->where('re.userId = :userId')
            ->andWhere('re.cardId = :cardId')
            ->setParameter('userId', $userId->toString())
            ->setParameter('cardId', $cardId)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
