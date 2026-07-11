<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\TerminEntity;
use App\Tests\DatabaseTestCase;
use Doctrine\ORM\EntityManagerInterface;

class TermineControllerTest extends DatabaseTestCase
{
    protected static function loadFixtures(EntityManagerInterface $entityManager): void
    {
        $probestunde = (new TerminEntity())
            ->setTitle('Probestunde zum Reinschnuppern')
            ->setDescription('Komm einfach vorbei und schau dir eine Probe an – völlig unverbindlich.')
            ->setStartsAt(new \DateTimeImmutable('2026-09-12 10:30:00'))
            ->setSortOrder(1);

        $proben = (new TerminEntity())
            ->setTitle('Proben')
            ->setDescription('Unsere regelmäßigen Proben.')
            ->setRecurrence('jeden Dienstag um 19 Uhr (ca. 2–3 Stunden)')
            ->setSortOrder(2);

        $entityManager->persist($probestunde);
        $entityManager->persist($proben);
        $entityManager->flush();
    }

    public function testTerminePageListsTermineFromDatabase(): void
    {
        $client = static::createClient();
        $client->request('GET', '/termine');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('body', 'Probestunde zum Reinschnuppern');
        $this->assertSelectorTextContains('body', '12. September 2026 um 10:30 Uhr');
        $this->assertSelectorTextContains('body', 'jeden Dienstag um 19 Uhr (ca. 2–3 Stunden)');
    }

    public function testTerminePageLinksToAnmeldung(): void
    {
        $client = static::createClient();
        $client->request('GET', '/termine');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('a[href="/anmeldung"]');
        // Dated Termine (Probestunde) link to the registration inside their card
        $this->assertSelectorExists('.card a[href="/anmeldung"]');
    }

    public function testTerminePageHasNoRegistrationForm(): void
    {
        $client = static::createClient();
        $client->request('GET', '/termine');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorNotExists('input[name="form_registration[name]"]');
    }

    public function testNavbarContainsTermineAndAnmeldungLinks(): void
    {
        $client = static::createClient();
        $client->request('GET', '/termine');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('a.nav-link[href="/termine"]');
        $this->assertSelectorExists('a.nav-link[href="/anmeldung"]');
    }
}
