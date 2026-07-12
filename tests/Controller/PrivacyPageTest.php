<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Tests\DatabaseTestCase;

class PrivacyPageTest extends DatabaseTestCase
{
    public function testPrivacyPageIsSuccessful(): void
    {
        $client = static::createClient();
        $client->request('GET', '/datenschutz');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Datenschutzerklärung');
    }

    public function testPrivacyPageCoversCoreTopics(): void
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

    public function testFooterLinksToPrivacyPolicy(): void
    {
        $client = static::createClient();
        $client->request('GET', '/');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('footer a[href="/datenschutz"]');
    }
}
