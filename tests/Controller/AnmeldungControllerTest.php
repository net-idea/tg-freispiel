<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\FormRegistrationEntity;
use App\Tests\DatabaseTestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\DomCrawler\Crawler;

class AnmeldungControllerTest extends DatabaseTestCase
{
    public function testAnmeldungPageIsSuccessful(): void
    {
        $client = static::createClient();
        $client->request('GET', '/anmeldung');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('body', 'Anmeldung zur Probestunde');
    }

    public function testAnmeldungPageShowsFinePrintForMinors(): void
    {
        $client = static::createClient();
        $client->request('GET', '/anmeldung');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('body', 'Einwilligung der Eltern');
    }

    public function testFormHasAllRequiredFields(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/anmeldung');

        $this->assertSelectorExists('input[name="form_registration[name]"]');
        $this->assertSelectorExists('input[name="form_registration[email]"]');
        $this->assertSelectorExists('input[name="form_registration[phone]"]');
        $this->assertSelectorExists('textarea[name="form_registration[motivation]"]');
        $this->assertSelectorExists('textarea[name="form_registration[roleReason]"]');
        $this->assertSelectorExists('textarea[name="form_registration[expectations]"]');
        $this->assertSelectorExists('input[name="form_registration[consent]"]');
        $this->assertSelectorExists('input[name="form_registration[_token]"]');

        // Role checkboxes
        $this->assertSelectorExists('input[type="checkbox"][name="form_registration[roleTypes][]"]');
        $this->assertCount(5, $crawler->filter('input[name="form_registration[roleTypes][]"]'));
        $this->assertSelectorTextContains('body', 'tragisch');
        $this->assertSelectorTextContains('body', 'böse');

        // Honeypots
        $this->assertSelectorExists('input[name="form_registration[emailrep]"]');
        $this->assertSelectorExists('input[name="form_registration[website]"]');
    }

    public function testConsentLabelLinksToDatenschutz(): void
    {
        $client = static::createClient();
        $client->request('GET', '/anmeldung');

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
        $this->assertArrayHasKey('phone', $json['errors']);
        $this->assertArrayHasKey('motivation', $json['errors']);
        $this->assertArrayHasKey('roleTypes', $json['errors']);
        $this->assertArrayHasKey('roleReason', $json['errors']);
        $this->assertArrayHasKey('expectations', $json['errors']);
        $this->assertArrayHasKey('consent', $json['errors']);
    }

    public function testAjaxSubmitWithValidDataReturnsSuccessAndPersists(): void
    {
        $client = static::createClient();

        $this->submitAjax($client, $this->validData());

        $this->assertResponseIsSuccessful();
        $json = $this->getJson($client);

        $this->assertTrue($json['success']);
        $this->assertNotEmpty($json['message']);

        $registrations = $this->getEntityManager()->getRepository(FormRegistrationEntity::class)->findAll();
        $this->assertCount(1, $registrations);
        $this->assertSame('Jane Doe', $registrations[0]->getName());
        $this->assertSame(['komisch', 'böse'], $registrations[0]->getRoleTypes());
        $this->assertSame('Weil Gegensätze reizen.', $registrations[0]->getRoleReason());
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

        $registrations = $this->getEntityManager()->getRepository(FormRegistrationEntity::class)->findAll();
        $this->assertCount(0, $registrations);
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
        $crawler = $client->request('GET', '/anmeldung');

        $form = $crawler->filter('button[type=submit]')->eq(0)->form();
        $form['form_registration[name]'] = '';
        $form['form_registration[email]'] = '';

        $client->submit($form);

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('.alert-danger');
        $this->assertSelectorTextContains('body', 'Bitte gib deinen Namen an.');
        $this->assertSelectorTextContains('body', 'Bitte wähle mindestens eine Rolle aus.');
    }

    /**
     * @return array<string, string|array<int, string>>
     */
    private function validData(): array
    {
        return [
            'name'         => 'Jane Doe',
            'email'        => 'jane@example.com',
            'phone'        => '+49 123 456789',
            'motivation'   => 'Ich liebe Theater.',
            'roleTypes'    => ['komisch', 'böse'],
            'roleReason'   => 'Weil Gegensätze reizen.',
            'expectations' => 'Spaß und Gemeinschaft.',
            'consent'      => '1',
        ];
    }

    /**
     * Fetch the page for a CSRF token, then POST the given data as Ajax.
     *
     * @param array<string, string|array<int, string>> $data
     */
    private function submitAjax(KernelBrowser $client, array $data): Crawler
    {
        $crawler = $client->request('GET', '/anmeldung');
        $data['_token'] = (string) $crawler->filter('input[name="form_registration[_token]"]')->attr('value');

        return $client->request(
            'POST',
            '/anmeldung',
            ['form_registration' => $data],
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
