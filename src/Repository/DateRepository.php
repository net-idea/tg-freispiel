<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\DateEntity;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<DateEntity>
 */
class DateRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DateEntity::class);
    }

    /**
     * Active dates that are either recurring (no start date) or still ahead,
     * ordered by sortOrder, then by start date.
     *
     * @return array<int, DateEntity>
     */
    public function findUpcoming(?\DateTimeImmutable $now = null): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.active = true')
            ->andWhere('t.startsAt IS NULL OR t.startsAt >= :now')
            ->setParameter('now', $now ?? new \DateTimeImmutable())
            ->orderBy('t.sortOrder', 'ASC')
            ->addOrderBy('t.startsAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * The next active one-off date (recurring slots have no start date and
     * are skipped).
     */
    public function findNext(?\DateTimeImmutable $now = null): ?DateEntity
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.active = true')
            ->andWhere('t.startsAt >= :now')
            ->setParameter('now', $now ?? new \DateTimeImmutable())
            ->orderBy('t.startsAt', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
