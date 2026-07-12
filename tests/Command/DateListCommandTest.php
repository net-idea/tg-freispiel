<?php

declare(strict_types=1);

namespace App\Tests\Command;

use App\Entity\DateEntity;
use App\Tests\DatabaseTestCase;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class DateListCommandTest extends DatabaseTestCase
{
    public function testListsStoredDates(): void
    {
        self::bootKernel();
        $em = $this->getEntityManager();

        $date = (new DateEntity())
            ->setTitle('Probestunde zum Reinschnuppern')
            ->setStartsAt(new \DateTimeImmutable('+2 weeks'));
        $em->persist($date);
        $em->flush();

        $tester = $this->makeTester();
        $tester->execute([]);

        $tester->assertCommandIsSuccessful();
        $this->assertStringContainsString('Probestunde zum Reinschnuppern', $tester->getDisplay());
    }

    public function testUpcomingFilterHidesInactiveDates(): void
    {
        self::bootKernel();
        $em = $this->getEntityManager();

        $inactive = (new DateEntity())
            ->setTitle('Versteckt')
            ->setStartsAt(new \DateTimeImmutable('+2 weeks'))
            ->setActive(false);
        $em->persist($inactive);
        $em->flush();

        $tester = $this->makeTester();
        $tester->execute(['--upcoming' => true]);

        $tester->assertCommandIsSuccessful();
        $this->assertStringNotContainsString('Versteckt', $tester->getDisplay());
    }

    private function makeTester(): CommandTester
    {
        $application = new Application(self::$kernel);

        return new CommandTester($application->find('app:date:list'));
    }
}
