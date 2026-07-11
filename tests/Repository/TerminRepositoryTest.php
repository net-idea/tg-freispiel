<?php

declare(strict_types=1);

namespace App\Tests\Repository;

use App\Entity\TerminEntity;
use App\Repository\TerminRepository;
use App\Tests\DatabaseTestCase;

class TerminRepositoryTest extends DatabaseTestCase
{
    public function testFindUpcomingReturnsActiveFutureAndRecurringTermine(): void
    {
        self::bootKernel();
        $em = $this->getEntityManager();

        $past = (new TerminEntity())
            ->setTitle('Vergangene Aufführung')
            ->setStartsAt(new \DateTimeImmutable('-1 week'));
        $future = (new TerminEntity())
            ->setTitle('Probestunde zum Reinschnuppern')
            ->setStartsAt(new \DateTimeImmutable('+2 weeks'))
            ->setSortOrder(1);
        $recurring = (new TerminEntity())
            ->setTitle('Proben')
            ->setRecurrence('jeden Dienstag um 19 Uhr (ca. 2–3 Stunden)')
            ->setSortOrder(2);
        $inactive = (new TerminEntity())
            ->setTitle('Versteckt')
            ->setStartsAt(new \DateTimeImmutable('+3 weeks'))
            ->setActive(false);

        foreach ([$past, $future, $recurring, $inactive] as $termin) {
            $em->persist($termin);
        }
        $em->flush();

        /** @var TerminRepository $repo */
        $repo = $em->getRepository(TerminEntity::class);
        $upcoming = $repo->findUpcoming();

        $this->assertCount(2, $upcoming);
        $this->assertSame('Probestunde zum Reinschnuppern', $upcoming[0]->getTitle());
        $this->assertSame('Proben', $upcoming[1]->getTitle());
    }

    public function testFindNextEventReturnsEarliestFutureDatedTermin(): void
    {
        self::bootKernel();
        $em = $this->getEntityManager();

        $later = (new TerminEntity())
            ->setTitle('Später')
            ->setStartsAt(new \DateTimeImmutable('+4 weeks'));
        $sooner = (new TerminEntity())
            ->setTitle('Früher')
            ->setStartsAt(new \DateTimeImmutable('+1 week'));
        $recurring = (new TerminEntity())
            ->setTitle('Proben')
            ->setRecurrence('jeden Dienstag um 19 Uhr');

        foreach ([$later, $sooner, $recurring] as $termin) {
            $em->persist($termin);
        }
        $em->flush();

        /** @var TerminRepository $repo */
        $repo = $em->getRepository(TerminEntity::class);
        $next = $repo->findNextEvent();

        $this->assertNotNull($next);
        $this->assertSame('Früher', $next->getTitle());
    }

    public function testFindNextEventReturnsNullWithoutDatedTermine(): void
    {
        self::bootKernel();

        /** @var TerminRepository $repo */
        $repo = $this->getEntityManager()->getRepository(TerminEntity::class);

        $this->assertNull($repo->findNextEvent());
    }
}
