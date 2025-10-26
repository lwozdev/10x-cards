<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine\Repository;

use App\Domain\Model\AiJob;
use App\Domain\Model\AiJobStatus;
use App\Domain\Repository\AiJobRepositoryInterface;
use App\Domain\Value\UserId;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AiJob>
 */
class DoctrineAiJobRepository extends ServiceEntityRepository implements AiJobRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AiJob::class);
    }

    public function findById(string $id): ?AiJob
    {
        return $this->find($id);
    }

    public function findByUser(UserId $userId, int $limit = 50): array
    {
        return $this->createQueryBuilder('aj')
            ->where('aj.userId = :userId')
            ->setParameter('userId', $userId->toString())
            ->orderBy('aj.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findByStatus(AiJobStatus $status, int $limit = 100): array
    {
        return $this->createQueryBuilder('aj')
            ->where('aj.status = :status')
            ->setParameter('status', $status)
            ->orderBy('aj.createdAt', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function save(AiJob $job): void
    {
        $this->getEntityManager()->persist($job);
        $this->getEntityManager()->flush();
    }

    public function countFailedByUser(UserId $userId): int
    {
        return $this->count([
            'userId' => $userId->toString(),
            'status' => AiJobStatus::FAILED
        ]);
    }
}
