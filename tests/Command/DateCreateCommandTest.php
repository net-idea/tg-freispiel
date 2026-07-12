<?php

declare(strict_types=1);

namespace App\Tests\Command;

use App\Entity\DateEntity;
use App\Tests\DatabaseTestCase;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class DateCreateCommandTest extends DatabaseTestCase
{
    public function testCreatesOneOffDate(): void
    {
        self::bootKernel();

        $tester = $this->makeTester();
        $tester->execute([
            'title'       => 'Sommerfest',
            '--starts-at' => '2026-08-01 18:00',
        ]);

        $tester->assertCommandIsSuccessful();

        $dates = $this->getEntityManager()->getRepository(DateEntity::class)->findAll();
        $this->assertCount(1, $dates);
        $this->assertSame('Sommerfest', $dates[0]->getTitle());
        $this->assertSame('2026-08-01 18:00', $dates[0]->getStartsAt()?->format('Y-m-d H:i'));
        $this->assertTrue($dates[0]->isActive());
    }

    public function testCreatesRecurringDate(): void
    {
        self::bootKernel();

        $tester = $this->makeTester();
        $tester->execute([
            'title'        => 'Proben',
            '--recurrence' => 'jeden Dienstag um 19 Uhr',
        ]);

        $tester->assertCommandIsSuccessful();

        $dates = $this->getEntityManager()->getRepository(DateEntity::class)->findAll();
        $this->assertCount(1, $dates);
        $this->assertSame('jeden Dienstag um 19 Uhr', $dates[0]->getRecurrence());
    }

    public function testFailsWithoutStartsAtOrRecurrence(): void
    {
        self::bootKernel();

        $tester = $this->makeTester();
        $exitCode = $tester->execute(['title' => 'Kaputt']);

        $this->assertNotSame(0, $exitCode);
        $this->assertCount(0, $this->getEntityManager()->getRepository(DateEntity::class)->findAll());
    }

    public function testFailsForInvalidStartsAt(): void
    {
        self::bootKernel();

        $tester = $this->makeTester();
        $exitCode = $tester->execute(['title' => 'Kaputt', '--starts-at' => 'not-a-date']);

        $this->assertNotSame(0, $exitCode);
        $this->assertCount(0, $this->getEntityManager()->getRepository(DateEntity::class)->findAll());
    }

    private function makeTester(): CommandTester
    {
        $application = new Application(self::$kernel);

        return new CommandTester($application->find('app:date:create'));
    }
}
