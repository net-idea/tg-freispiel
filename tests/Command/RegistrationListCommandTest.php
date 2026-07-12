<?php

declare(strict_types=1);

namespace App\Tests\Command;

use App\Entity\FormRegistrationEntity;
use App\Tests\DatabaseTestCase;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class RegistrationListCommandTest extends DatabaseTestCase
{
    public function testListsStoredRegistrations(): void
    {
        self::bootKernel();
        $em = $this->getEntityManager();

        $registration = (new FormRegistrationEntity())
            ->setName('Jane Doe')
            ->setEmailAddress('jane@example.com')
            ->setPhone('+49 123 456789')
            ->setMotivation('Ich liebe Theater.')
            ->setRoleTypes(['komisch'])
            ->setRoleReason('Weil ich gerne lache.')
            ->setExpectations('Spaß.')
            ->setConsent(true);
        $em->persist($registration);
        $em->flush();

        $tester = $this->makeTester();
        $tester->execute([]);

        $tester->assertCommandIsSuccessful();
        $this->assertStringContainsString('Jane Doe', $tester->getDisplay());
        $this->assertStringContainsString('komisch', $tester->getDisplay());
    }

    public function testCsvOutput(): void
    {
        self::bootKernel();

        $tester = $this->makeTester();
        $tester->execute(['--csv' => true]);

        $tester->assertCommandIsSuccessful();
        $this->assertStringContainsString('ID,', $tester->getDisplay());
    }

    private function makeTester(): CommandTester
    {
        $application = new Application(self::$kernel);

        return new CommandTester($application->find('app:registration:list'));
    }
}
