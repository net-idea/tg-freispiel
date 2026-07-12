<?php

declare(strict_types=1);

namespace App\Tests\Repository;

use App\Entity\DateEntity;
use App\Repository\DateRepository;
use App\Tests\DatabaseTestCase;

class DateRepositoryTest extends DatabaseTestCase
{
    public function testFindUpcomingReturnsActiveFutureAndRecurringDates(): void
    {
        self::bootKernel();
        $em = $this->getEntityManager();

        $past = (new DateEntity())
            ->setTitle('Vergangene Aufführung')
            ->setStartsAt(new \DateTimeImmutable('-1 week'));
        $future = (new DateEntity())
            ->setTitle('Probestunde zum Reinschnuppern')
            ->setStartsAt(new \DateTimeImmutable('+2 weeks'))
            ->setSortOrder(1);
        $recurring = (new DateEntity())
            ->setTitle('Proben')
            ->setRecurrence('jeden Dienstag um 19 Uhr (ca. 2–3 Stunden)')
            ->setSortOrder(2);
        $inactive = (new DateEntity())
            ->setTitle('Versteckt')
            ->setStartsAt(new \DateTimeImmutable('+3 weeks'))
            ->setActive(false);

        foreach ([$past, $future, $recurring, $inactive] as $date) {
            $em->persist($date);
        }
        $em->flush();

        /** @var DateRepository $repo */
        $repo = $em->getRepository(DateEntity::class);
        $upcoming = $repo->findUpcoming();

        $this->assertCount(2, $upcoming);
        $this->assertSame('Probestunde zum Reinschnuppern', $upcoming[0]->getTitle());
        $this->assertSame('Proben', $upcoming[1]->getTitle());
    }

    public function testFindNextReturnsEarliestFutureDate(): void
    {
        self::bootKernel();
        $em = $this->getEntityManager();

        $later = (new DateEntity())
            ->setTitle('Später')
            ->setStartsAt(new \DateTimeImmutable('+4 weeks'));
        $sooner = (new DateEntity())
            ->setTitle('Früher')
            ->setStartsAt(new \DateTimeImmutable('+1 week'));
        $recurring = (new DateEntity())
            ->setTitle('Proben')
            ->setRecurrence('jeden Dienstag um 19 Uhr');

        foreach ([$later, $sooner, $recurring] as $date) {
            $em->persist($date);
        }
        $em->flush();

        /** @var DateRepository $repo */
        $repo = $em->getRepository(DateEntity::class);
        $next = $repo->findNext();

        $this->assertNotNull($next);
        $this->assertSame('Früher', $next->getTitle());
    }

    public function testFindNextReturnsNullWithoutDates(): void
    {
        self::bootKernel();

        /** @var DateRepository $repo */
        $repo = $this->getEntityManager()->getRepository(DateEntity::class);

        $this->assertNull($repo->findNext());
    }
}
