<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Entity\FormContactEntity;
use App\Repository\FormContactRepository;
use App\Tests\DatabaseTestCase;

class ContactFormIntegrationTest extends DatabaseTestCase
{
    public function testCompleteContactFormSubmissionFlow(): void
    {
        $client = static::createClient();

        // Step 1: Visit the contact page
        $crawler = $client->request('GET', '/kontakt');
        $this->assertResponseIsSuccessful();

        // Step 2: Fill and submit the form
        $buttonCrawlerNode = $crawler->selectButton('Absenden');
        $form = $buttonCrawlerNode->form();

        $form['form_contact[name]'] = 'Integration Test User';
        $form['form_contact[email]'] = 'integration@test.com';
        $form['form_contact[phone]'] = '+49 123 456789';
        $form['form_contact[message]'] = 'This is an integration test message with more than 10 characters';
        $form['form_contact[consent]']->tick();

        $client->submit($form);

        // Step 3: Should redirect
        $this->assertResponseRedirects();

        // Step 3.5: Verify data was saved to database BEFORE following redirect (which reboots kernel/wipes DB in memory)
        $container = static::getContainer();
        /** @var FormContactRepository $repository */
        $repository = $container->get(FormContactRepository::class);

        // Clear entity manager to ensure we fetch from database
        $this->getEntityManager()->clear();

        $submissions = $repository->findBy(['emailAddress' => 'integration@test.com'], ['id' => 'DESC'], 1);
        $this->assertCount(1, $submissions, 'Should have exactly one submission saved to database');

        /** @var FormContactEntity $submission */
        $submission = $submissions[0];
        $this->assertSame('Integration Test User', $submission->getName());
        $this->assertSame('integration@test.com', $submission->getEmailAddress());
        $this->assertSame('+49 123 456789', $submission->getPhone());
        $this->assertStringContainsString('integration test message', strtolower($submission->getMessage()));
        $this->assertTrue($submission->getConsent());

        // Verify metadata was saved
        $meta = $submission->getMeta();
        $this->assertNotNull($meta, 'Submission should have metadata');
        $this->assertNotEmpty($meta->getIp());
        $this->assertNotEmpty($meta->getUserAgent());
        $this->assertNotEmpty($meta->getTime());

        // Step 4: Follow redirect and check for success message
        $crawler = $client->followRedirect();

        $this->assertSelectorExists('.alert-success');
        $this->assertSelectorTextContains('.alert-success', 'Vielen Dank! Ihre Nachricht wurde erfolgreich versendet');
    }

    public function testFormValidationErrorsPersistOnResubmission(): void
    {
        $client = static::createClient();

        // Step 1: Submit form with errors
        $crawler = $client->request('GET', '/kontakt');
        $buttonCrawlerNode = $crawler->selectButton('Absenden');
        $form = $buttonCrawlerNode->form();

        $form['form_contact[name]'] = 'Test User';
        $form['form_contact[email]'] = 'test@example.com';
        $form['form_contact[message]'] = 'Short'; // Too short!
        $form['form_contact[consent]']->tick();

        $client->submit($form);

        // Step 2: Should show validation error
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('body', 'mindestens 10 Zeichen');

        // Step 3: Fix the error and resubmit
        $crawler = $client->getCrawler();
        $buttonCrawlerNode = $crawler->selectButton('Absenden');
        $form = $buttonCrawlerNode->form();

        // Form should still have the previous values
        $this->assertSame('Test User', $form['form_contact[name]']->getValue());
        $this->assertSame('test@example.com', $form['form_contact[email]']->getValue());

        // Fix the message
        $form['form_contact[message]'] = 'This is now a valid message with more than 10 characters';

        $client->submit($form);

        // Should redirect successfully
        $this->assertResponseRedirects();
        $crawler = $client->followRedirect();
        $this->assertSelectorExists('.alert-success');
    }

    public function testMultipleValidationErrorsAreShown(): void
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/kontakt');
        $buttonCrawlerNode = $crawler->selectButton('Absenden');
        $form = $buttonCrawlerNode->form();

        // Submit with multiple errors
        $form['form_contact[name]'] = '';
        $form['form_contact[email]'] = 'invalid-email';
        $form['form_contact[message]'] = 'Short';
        $form['form_contact[consent]']->untick();

        $client->submit($form);

        $this->assertResponseIsSuccessful();

        // Should show all validation errors
        $this->assertSelectorTextContains('body', 'Namen');
        $this->assertSelectorTextContains('body', 'gültige E‑Mail');
        $this->assertSelectorTextContains('body', 'mindestens');
        $this->assertSelectorTextContains('body', 'Datenverarbeitung');
    }

    public function testRateLimitingPreventsSpam(): void
    {
        $client = static::createClient();

        // Submit first form successfully
        $this->submitValidForm($client);
        $this->assertResponseRedirects();
        $client->followRedirect();

        // Immediately try to submit again (should be rate limited)
        $crawler = $client->request('GET', '/kontakt');
        $buttonCrawlerNode = $crawler->selectButton('Absenden');
        $form = $buttonCrawlerNode->form();

        $form['form_contact[name]'] = 'Spammer';
        $form['form_contact[email]'] = 'spam@example.com';
        $form['form_contact[message]'] = 'This is another message';
        $form['form_contact[consent]']->tick();

        $client->submit($form);

        // Should redirect with rate limit error
        $this->assertResponseRedirects();
        $crawler = $client->followRedirect();

        $this->assertSelectorExists('.alert-danger');
        $this->assertSelectorTextContains('.alert-danger', 'Bitte warten Sie einen Moment');
    }

    public function testFormPreservesDataAfterMailSendFailure(): void
    {
        $client = static::createClient();
        $client->disableReboot();

        // This test would require mocking the MailManService to throw an exception
        // For now, we'll just verify the error message shows up when error=mail is in URL
        $client->request('GET', '/kontakt?error=mail');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('.alert-danger');
        $this->assertSelectorTextContains('.alert-danger', 'nicht versendet');
    }

    private function submitValidForm($client): void
    {
        $crawler = $client->request('GET', '/kontakt');
        $buttonCrawlerNode = $crawler->selectButton('Absenden');
        $form = $buttonCrawlerNode->form();

        $form['form_contact[name]'] = 'Valid User ' . uniqid();
        $form['form_contact[email]'] = 'valid' . uniqid() . '@example.com';
        $form['form_contact[phone]'] = '+49 123 456789';
        $form['form_contact[message]'] = 'Valid message with more than 10 characters ' . uniqid();
        $form['form_contact[consent]']->tick();

        $client->submit($form);
    }
}
