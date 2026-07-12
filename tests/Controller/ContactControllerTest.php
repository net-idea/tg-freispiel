<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\FormContactEntity;
use App\Tests\DatabaseTestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\DomCrawler\Crawler;

class ContactControllerTest extends DatabaseTestCase
{
    public function testContactPageIsSuccessful(): void
    {
        $client = static::createClient();
        $client->request('GET', '/kontakt');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h2', 'Kontaktformular');
    }

    public function testFormHasAllRequiredFields(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/kontakt');

        $this->assertSelectorExists('input[name="form_contact[name]"]');
        $this->assertSelectorExists('input[name="form_contact[email]"]');
        $this->assertSelectorExists('input[name="form_contact[phone]"]');
        $this->assertSelectorExists('textarea[name="form_contact[message]"]');
        $this->assertSelectorExists('input[name="form_contact[consent]"]');
        $this->assertSelectorExists('input[name="form_contact[copy]"]');
        $this->assertSelectorExists('input[name="form_contact[_token]"]');

        // Honeypots
        $this->assertSelectorExists('input[name="form_contact[emailrep]"]');
        $this->assertSelectorExists('input[name="form_contact[website]"]');

        $csrfToken = $crawler->filter('input[name="form_contact[_token]"]')->attr('value');
        $this->assertNotEmpty($csrfToken);
    }

    public function testConsentLabelLinksToPrivacyPolicy(): void
    {
        $client = static::createClient();
        $client->request('GET', '/kontakt');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('a[href="/datenschutz"]');
    }

    public function testAjaxSubmitWithInvalidDataReturnsJsonErrors(): void
    {
        $client = static::createClient();

        $this->submitAjax($client, ['name' => '']);

        $this->assertResponseStatusCodeSame(422);
        $json = $this->getJson($client);

        $this->assertFalse($json['success']);
        $this->assertArrayHasKey('errors', $json);
        $this->assertArrayHasKey('name', $json['errors']);
        $this->assertArrayHasKey('email', $json['errors']);
        $this->assertArrayHasKey('message', $json['errors']);
        $this->assertArrayHasKey('consent', $json['errors']);
    }

    public function testAjaxSubmitWithValidDataReturnsSuccessAndPersists(): void
    {
        $client = static::createClient();

        $this->submitAjax($client, $this->validData());

        $this->assertResponseIsSuccessful();
        $json = $this->getJson($client);

        $this->assertTrue($json['success']);
        $this->assertStringContainsString('Vielen Dank für deine Nachricht!', $json['message']);

        $contacts = $this->getEntityManager()->getRepository(FormContactEntity::class)->findAll();
        $this->assertCount(1, $contacts);
        $this->assertSame('John Doe', $contacts[0]->getName());
    }

    public function testAjaxSubmitWithHoneypotPretendsSuccessWithoutPersisting(): void
    {
        $client = static::createClient();

        $data = $this->validData();
        $data['website'] = 'http://spam.example.com';
        $this->submitAjax($client, $data);

        $this->assertResponseIsSuccessful();
        $json = $this->getJson($client);

        $this->assertTrue($json['success']);

        $contacts = $this->getEntityManager()->getRepository(FormContactEntity::class)->findAll();
        $this->assertCount(0, $contacts);
    }

    public function testSecondAjaxSubmitIsRateLimited(): void
    {
        $client = static::createClient();

        $this->submitAjax($client, $this->validData());
        $this->assertResponseIsSuccessful();

        $this->submitAjax($client, $this->validData());
        $this->assertResponseStatusCodeSame(429);
        $json = $this->getJson($client);
        $this->assertFalse($json['success']);
    }

    public function testNonAjaxSubmitWithInvalidDataRerendersPage(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/kontakt');

        $form = $crawler->filter('button[type=submit]')->eq(0)->form();
        $form['form_contact[name]'] = '';
        $form['form_contact[email]'] = '';

        $client->submit($form);

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('.alert-danger');
        $this->assertSelectorTextContains('body', 'Bitte gib deinen Namen an.');
    }

    /**
     * @return array<string, string>
     */
    private function validData(): array
    {
        return [
            'name'    => 'John Doe',
            'email'   => 'john@example.com',
            'phone'   => '+49 123 456789',
            'message' => 'This is a valid test message.',
            'consent' => '1',
        ];
    }

    /**
     * Fetch the page for a CSRF token, then POST the given data as Ajax.
     *
     * @param array<string, string> $data
     */
    private function submitAjax(KernelBrowser $client, array $data): Crawler
    {
        $crawler = $client->request('GET', '/kontakt');
        $data['_token'] = (string) $crawler->filter('input[name="form_contact[_token]"]')->attr('value');

        return $client->request(
            'POST',
            '/kontakt',
            ['form_contact' => $data],
            [],
            ['HTTP_X-Requested-With' => 'XMLHttpRequest']
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function getJson(KernelBrowser $client): array
    {
        $content = (string) $client->getResponse()->getContent();
        $this->assertJson($content);

        return (array) json_decode($content, true);
    }
}
