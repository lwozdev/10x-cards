<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine\Repository;

use App\Domain\Model\Card;
use App\Domain\Repository\CardRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Card>
 */
class DoctrineCardRepository extends ServiceEntityRepository implements CardRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Card::class);
    }

    public function findById(string $id): ?Card
    {
        return $this->find($id);
    }

    public function findActiveBySetId(string $setId): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.setId = :setId')
            ->andWhere('c.deletedAt IS NULL')
            ->setParameter('setId', $setId)
            ->orderBy('c.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function save(Card $card): void
    {
        $this->getEntityManager()->persist($card);
        $this->getEntityManager()->flush();
    }

    public function saveAll(array $cards): void
    {
        $em = $this->getEntityManager();

        foreach ($cards as $card) {
            $em->persist($card);
        }

        $em->flush();
    }

    public function softDelete(Card $card): void
    {
        $card->softDelete(new \DateTimeImmutable());
        $this->getEntityManager()->flush();
    }

    public function countActiveBySetId(string $setId): int
    {
        return (int) $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->where('c.setId = :setId')
            ->andWhere('c.deletedAt IS NULL')
            ->setParameter('setId', $setId)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
