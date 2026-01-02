<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine\Repository;

use App\Domain\Model\Set;
use App\Domain\Repository\SetRepositoryInterface;
use App\Domain\Value\UserId;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Set>
 */
class DoctrineSetRepository extends ServiceEntityRepository implements SetRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Set::class);
    }

    public function findById(string $id): ?Set
    {
        return $this->find($id);
    }

    public function findOwnedBy(UserId $ownerId): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.ownerId = :ownerId')
            ->setParameter('ownerId', $ownerId->toString())
            ->orderBy('s.updatedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findActiveOwnedBy(UserId $ownerId, int $limit = 100, int $offset = 0): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.ownerId = :ownerId')
            ->andWhere('s.deletedAt IS NULL')
            ->setParameter('ownerId', $ownerId->toString())
            ->orderBy('s.updatedAt', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();
    }

    public function save(Set $set): void
    {
        $this->getEntityManager()->persist($set);
        $this->getEntityManager()->flush();
    }

    public function softDelete(Set $set): void
    {
        $set->softDelete(new \DateTimeImmutable());
        $this->getEntityManager()->flush();
    }

    public function existsByOwnerAndName(UserId $ownerId, string $name): bool
    {
        return $this->count([
            'ownerId' => $ownerId->toString(),
            'name' => $name,
            'deletedAt' => null
        ]) > 0;
    }
}
