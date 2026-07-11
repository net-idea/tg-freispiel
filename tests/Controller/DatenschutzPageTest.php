<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Tests\DatabaseTestCase;

class DatenschutzPageTest extends DatabaseTestCase
{
    public function testDatenschutzPageIsSuccessful(): void
    {
        $client = static::createClient();
        $client->request('GET', '/datenschutz');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Datenschutzerklärung');
    }

    public function testDatenschutzPageCoversCoreTopics(): void
    {
        $client = static::createClient();
        $client->request('GET', '/datenschutz');

        $this->assertResponseIsSuccessful();
        // Verantwortlicher + Rechtsgrundlage + Betroffenenrechte + keine Weitergabe
        $this->assertSelectorTextContains('body', 'Verantwortlich');
        $this->assertSelectorTextContains('body', 'DSGVO');
        $this->assertSelectorTextContains('body', 'nicht an Dritte weiter');
        $this->assertSelectorTextContains('body', 'Auskunft');
        $this->assertSelectorTextContains('body', 'Löschung');
        $this->assertSelectorTextContains('body', 'Beschwerde');
    }

    public function testFooterLinksToDatenschutz(): void
    {
        $client = static::createClient();
        $client->request('GET', '/');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('footer a[href="/datenschutz"]');
    }
}
