<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class HomeControllerTest extends WebTestCase
{
    public function testHomepageIsSuccessful(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Willkommen bei der Theatergruppe Freispiel');
    }

    public function testContactPageIsSuccessful(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/contact');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Kontaktieren Sie uns');
    }

    public function testContactFormIsPresent(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/contact');

        $this->assertResponseIsSuccessful();
        
        // Check form fields are present
        $this->assertCount(1, $crawler->filter('input[name="contact[name]"]'));
        $this->assertCount(1, $crawler->filter('input[name="contact[email]"]'));
        $this->assertCount(1, $crawler->filter('textarea[name="contact[message]"]'));
    }
}
