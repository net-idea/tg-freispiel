<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Tests\DatabaseTestCase;

class ContactControllerTest extends DatabaseTestCase
{
    public function testContactPageIsSuccessful(): void
    {
        $client = static::createClient();
        $client->request('GET', '/kontakt');

        $this->assertResponseIsSuccessful();
        // Template now uses a form header '✉️ Kontaktformular'
        $this->assertSelectorTextContains('h2', 'Kontaktformular');
    }

    public function testSubmitEmptyFormShowsValidationErrors(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/kontakt');

        // Select the form submit button (look up by type to avoid exact label dependency)
        $buttonCrawlerNode = $crawler->filter('button[type=submit]')->eq(0);
        $form = $buttonCrawlerNode->form();

        // Submit with empty values
        $form['form_contact[name]'] = '';
        $form['form_contact[email]'] = '';
        $form['form_contact[message]'] = '';
        $form['form_contact[consent]']->untick();

        $client->submit($form);

        $this->assertResponseIsSuccessful(); // Should return 200 (re-render)

        // Check for validation errors - not CSRF errors
        $this->assertSelectorTextContains('body', 'Bitte geben Sie Ihren Namen an.');
        $this->assertSelectorTextContains('body', 'Bitte geben Sie Ihre E‑Mail‑Adresse an.');
        $this->assertSelectorTextContains('body', 'Bitte geben Sie eine Nachricht ein.');
        $this->assertSelectorTextContains('body', 'Bitte stimmen Sie der Datenverarbeitung zu.');

        // Ensure no CSRF error is shown
        $this->assertSelectorNotExists('body:contains("CSRF")');
        $this->assertSelectorNotExists('body:contains("csrf")');
    }

    public function testSubmitWithOnlyNameShowsOtherValidationErrors(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/kontakt');

        $buttonCrawlerNode = $crawler->filter('button[type=submit]')->eq(0);
        $form = $buttonCrawlerNode->form();

        $form['form_contact[name]'] = 'John Doe';
        $form['form_contact[email]'] = '';
        $form['form_contact[message]'] = '';
        $form['form_contact[consent]']->untick();

        $client->submit($form);

        $this->assertResponseIsSuccessful();

        // Name error should not appear, others should
        $this->assertSelectorNotExists('body:contains("Bitte geben Sie Ihren Namen an.")');
        $this->assertSelectorTextContains('body', 'Bitte geben Sie Ihre E‑Mail‑Adresse an.');
        $this->assertSelectorTextContains('body', 'Bitte geben Sie eine Nachricht ein.');
        $this->assertSelectorTextContains('body', 'Bitte stimmen Sie der Datenverarbeitung zu.');
    }

    public function testSubmitWithInvalidEmailShowsError(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/kontakt');

        $buttonCrawlerNode = $crawler->filter('button[type=submit]')->eq(0);
        $form = $buttonCrawlerNode->form();

        $form['form_contact[name]'] = 'John Doe';
        $form['form_contact[email]'] = 'not-a-valid-email';
        $form['form_contact[message]'] = 'This is a test message';
        $form['form_contact[consent]']->tick();

        $client->submit($form);

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('body', 'Bitte geben Sie eine gültige E‑Mail‑Adresse an.');
    }

    public function testSubmitWithTooShortMessageShowsError(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/kontakt');

        $buttonCrawlerNode = $crawler->filter('button[type=submit]')->eq(0);
        $form = $buttonCrawlerNode->form();

        $form['form_contact[name]'] = 'John Doe';
        $form['form_contact[email]'] = 'john@example.com';
        $form['form_contact[message]'] = 'Short'; // Less than 10 characters
        $form['form_contact[consent]']->tick();

        $client->submit($form);

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('body', 'Bitte geben Sie mindestens 10 Zeichen ein.');
    }

    public function testSubmitWithoutConsentShowsError(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/kontakt');

        $buttonCrawlerNode = $crawler->filter('button[type=submit]')->eq(0);
        $form = $buttonCrawlerNode->form();

        $form['form_contact[name]'] = 'John Doe';
        $form['form_contact[email]'] = 'john@example.com';
        $form['form_contact[message]'] = 'This is a valid test message with more than 10 characters';
        $form['form_contact[consent]']->untick();

        $client->submit($form);

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('body', 'Bitte stimmen Sie der Datenverarbeitung zu.');
    }

    public function testSubmitValidFormRedirectsWithSuccess(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/kontakt');

        $buttonCrawlerNode = $crawler->filter('button[type=submit]')->eq(0);
        $form = $buttonCrawlerNode->form();

        $form['form_contact[name]'] = 'John Doe';
        $form['form_contact[email]'] = 'john@example.com';
        $form['form_contact[phone]'] = '+49 123 456789';
        $form['form_contact[message]'] = 'This is a valid test message with more than 10 characters';
        $form['form_contact[consent]']->tick();

        $client->submit($form);

        // Should redirect to the contact page with success parameter
        $this->assertResponseRedirects();
        $crawler = $client->followRedirect();

        // Check for success message
        $this->assertSelectorExists('.alert-success');
        $this->assertSelectorTextContains('.alert-success', 'Vielen Dank! Ihre Nachricht wurde erfolgreich versendet');
    }

    public function testFormHasAllRequiredFields(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/kontakt');

        // Check that all form fields exist
        $this->assertSelectorExists('input[name="form_contact[name]"]');
        $this->assertSelectorExists('input[name="form_contact[email]"]');
        $this->assertSelectorExists('input[name="form_contact[phone]"]');
        $this->assertSelectorExists('textarea[name="form_contact[message]"]');
        $this->assertSelectorExists('input[name="form_contact[consent]"]');
        $this->assertSelectorExists('input[name="form_contact[copy]"]');

        // Check that CSRF token field exists
        $this->assertSelectorExists('input[name="form_contact[_token]"]');
        $csrfToken = $crawler->filter('input[name="form_contact[_token]"]')->attr('value');
        $this->assertNotEmpty($csrfToken, 'CSRF token should not be empty');
    }

    public function testFormHasHoneypotFields(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/kontakt');

        // Check that honeypot fields exist and are hidden
        $this->assertSelectorExists('input[name="form_contact[emailrep]"]');
        $this->assertSelectorExists('input[name="form_contact[website]"]');

        // Honeypot fields should have visually-hidden class or inline style
        $emailrepField = $crawler->filter('input[name="form_contact[emailrep]"]');
        $websiteField = $crawler->filter('input[name="form_contact[website]"]');

        $this->assertStringContainsString('visually-hidden', $emailrepField->attr('class'));
        $this->assertStringContainsString('visually-hidden', $websiteField->attr('class'));
    }

    public function testHoneypotTriggeredPretendSuccess(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/kontakt');

        $buttonCrawlerNode = $crawler->filter('button[type=submit]')->eq(0);
        $form = $buttonCrawlerNode->form();

        // Fill honeypot field (should trigger spam protection)
        $form['form_contact[name]'] = 'Spammer';
        $form['form_contact[email]'] = 'spam@example.com';
        $form['form_contact[phone]'] = '+49 123 456789';
        $form['form_contact[message]'] = 'This is a spam message';
        $form['form_contact[consent]']->tick();
        $form['form_contact[website]'] = 'http://spam-website.com';

        $client->submit($form);

        // Should redirect to pretend success (without actually sending email)
        $this->assertResponseRedirects();
        $crawler = $client->followRedirect();

        // Should show success message even though it's spam
        $this->assertSelectorExists('.alert-success');
    }

    public function testPhoneFieldIsOptional(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/kontakt');

        $buttonCrawlerNode = $crawler->filter('button[type=submit]')->eq(0);
        $form = $buttonCrawlerNode->form();

        // Submit without phone number
        $form['form_contact[name]'] = 'John Doe';
        $form['form_contact[email]'] = 'john@example.com';
        $form['form_contact[phone]'] = '';
        $form['form_contact[message]'] = 'This is a valid test message';
        $form['form_contact[consent]']->tick();

        $client->submit($form);

        // Should redirect successfully
        $this->assertResponseRedirects();
        $crawler = $client->followRedirect();

        $this->assertSelectorExists('.alert-success');
    }

    public function testInvalidCsrfTokenShowsError(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/kontakt');

        $buttonCrawlerNode = $crawler->filter('button[type=submit]')->eq(0);
        $form = $buttonCrawlerNode->form();

        // Fill form with valid data
        $form['form_contact[name]'] = 'John Doe';
        $form['form_contact[email]'] = 'john@example.com';
        $form['form_contact[message]'] = 'This is a valid test message';
        $form['form_contact[consent]']->tick();

        // Tamper with CSRF token
        $form['form_contact[_token]'] = 'INVALID_TOKEN_12345';

        $client->submit($form);

        // Should show form again with error (form is not valid)
        $this->assertResponseIsSuccessful();
        // The form should not be valid and should show validation errors
        $this->assertSelectorExists('.alert-danger');
    }
}
