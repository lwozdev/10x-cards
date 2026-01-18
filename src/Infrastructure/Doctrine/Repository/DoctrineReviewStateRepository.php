<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine\Repository;

use App\Domain\Model\ReviewState;
use App\Domain\Repository\ReviewStateRepositoryInterface;
use App\Domain\Value\UserId;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ReviewState>
 */
class DoctrineReviewStateRepository extends ServiceEntityRepository implements ReviewStateRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ReviewState::class);
    }

    public function findByUserAndCard(UserId $userId, string $cardId): ?ReviewState
    {
        return $this->findOneBy([
            'userId' => $userId->toString(),
            'cardId' => $cardId,
        ]);
    }

    public function findDueForUser(UserId $userId, \DateTimeImmutable $now, int $limit = 20): array
    {
        return $this->createQueryBuilder('rs')
            ->where('rs.userId = :userId')
            ->andWhere('rs.dueAt <= :now')
            ->setParameter('userId', $userId->toString())
            ->setParameter('now', $now)
            ->orderBy('rs.dueAt', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function save(ReviewState $state): void
    {
        $this->getEntityManager()->persist($state);
        $this->getEntityManager()->flush();
    }

    public function countDueForUser(UserId $userId, \DateTimeImmutable $now): int
    {
        return (int) $this->createQueryBuilder('rs')
            ->select('COUNT(rs.userId)')
            ->where('rs.userId = :userId')
            ->andWhere('rs.dueAt <= :now')
            ->setParameter('userId', $userId->toString())
            ->setParameter('now', $now)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
