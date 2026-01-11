<?php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\FormContactEntity;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class FormContactEntityTest extends TestCase
{
    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        $this->validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();
    }

    public function testEntityCanBeCreated(): void
    {
        $entity = new FormContactEntity();

        $this->assertInstanceOf(FormContactEntity::class, $entity);
        $this->assertNull($entity->getId());
        $this->assertInstanceOf(\DateTimeImmutable::class, $entity->getCreatedAt());
    }

    public function testSettersAndGetters(): void
    {
        $entity = new FormContactEntity();

        $entity->setName('John Doe');
        $this->assertSame('John Doe', $entity->getName());

        $entity->setEmailAddress('john@example.com');
        $this->assertSame('john@example.com', $entity->getEmailAddress());

        $entity->setPhone('+49 123 456789');
        $this->assertSame('+49 123 456789', $entity->getPhone());

        $entity->setMessage('This is a test message');
        $this->assertSame('This is a test message', $entity->getMessage());

        $entity->setConsent(true);
        $this->assertTrue($entity->getConsent());

        $entity->setCopy(false);
        $this->assertFalse($entity->getCopy());

        $entity->setEmailrep('honeypot@example.com');
        $this->assertSame('honeypot@example.com', $entity->getEmailrep());
    }

    public function testValidationFailsForEmptyName(): void
    {
        $entity = new FormContactEntity();
        $entity->setName('');
        $entity->setEmailAddress('john@example.com');
        $entity->setMessage('Valid message with more than 10 characters');
        $entity->setConsent(true);

        $violations = $this->validator->validate($entity);

        $this->assertGreaterThan(0, count($violations));
        $this->assertStringContainsString('Namen', (string) $violations->get(0)->getMessage());
    }

    public function testValidationFailsForEmptyEmail(): void
    {
        $entity = new FormContactEntity();
        $entity->setName('John Doe');
        $entity->setEmailAddress('');
        $entity->setMessage('Valid message with more than 10 characters');
        $entity->setConsent(true);

        $violations = $this->validator->validate($entity);

        $this->assertGreaterThan(0, count($violations));
        $this->assertStringContainsString('E‑Mail', (string) $violations->get(0)->getMessage());
    }

    public function testValidationFailsForInvalidEmail(): void
    {
        $entity = new FormContactEntity();
        $entity->setName('John Doe');
        $entity->setEmailAddress('not-a-valid-email');
        $entity->setMessage('Valid message with more than 10 characters');
        $entity->setConsent(true);

        $violations = $this->validator->validate($entity);

        $this->assertGreaterThan(0, count($violations));
        $this->assertStringContainsString('gültige', (string) $violations->get(0)->getMessage());
    }

    public function testValidationFailsForEmptyMessage(): void
    {
        $entity = new FormContactEntity();
        $entity->setName('John Doe');
        $entity->setEmailAddress('john@example.com');
        $entity->setMessage('');
        $entity->setConsent(true);

        $violations = $this->validator->validate($entity);

        $this->assertGreaterThan(0, count($violations));
        $this->assertStringContainsString('Nachricht', (string) $violations->get(0)->getMessage());
    }

    public function testValidationFailsForTooShortMessage(): void
    {
        $entity = new FormContactEntity();
        $entity->setName('John Doe');
        $entity->setEmailAddress('john@example.com');
        $entity->setMessage('Short'); // Less than 10 characters
        $entity->setConsent(true);

        $violations = $this->validator->validate($entity);

        $this->assertGreaterThan(0, count($violations));
        $this->assertStringContainsString('mindestens', (string) $violations->get(0)->getMessage());
    }

    public function testValidationFailsForTooLongMessage(): void
    {
        $entity = new FormContactEntity();
        $entity->setName('John Doe');
        $entity->setEmailAddress('john@example.com');
        $entity->setMessage(str_repeat('a', 5001)); // More than 5000 characters
        $entity->setConsent(true);

        $violations = $this->validator->validate($entity);

        $this->assertGreaterThan(0, count($violations));
        $this->assertStringContainsString('höchstens', (string) $violations->get(0)->getMessage());
    }

    public function testValidationFailsForFalseConsent(): void
    {
        $entity = new FormContactEntity();
        $entity->setName('John Doe');
        $entity->setEmailAddress('john@example.com');
        $entity->setMessage('Valid message with more than 10 characters');
        $entity->setConsent(false);

        $violations = $this->validator->validate($entity);

        $this->assertGreaterThan(0, count($violations));
        $this->assertStringContainsString('Datenverarbeitung', (string) $violations->get(0)->getMessage());
    }

    public function testValidationPassesForValidEntity(): void
    {
        $entity = new FormContactEntity();
        $entity->setName('John Doe');
        $entity->setEmailAddress('john@example.com');
        $entity->setPhone('+49 123 456789');
        $entity->setMessage('This is a valid test message with more than 10 characters');
        $entity->setConsent(true);

        $violations = $this->validator->validate($entity);

        $this->assertCount(0, $violations);
    }

    public function testValidationPassesWithoutOptionalPhone(): void
    {
        $entity = new FormContactEntity();
        $entity->setName('John Doe');
        $entity->setEmailAddress('john@example.com');
        $entity->setPhone('');
        $entity->setMessage('This is a valid test message with more than 10 characters');
        $entity->setConsent(true);

        $violations = $this->validator->validate($entity);

        $this->assertCount(0, $violations);
    }

    public function testValidationFailsForTooLongName(): void
    {
        $entity = new FormContactEntity();
        $entity->setName(str_repeat('a', 121)); // More than 120 characters
        $entity->setEmailAddress('john@example.com');
        $entity->setMessage('Valid message with more than 10 characters');
        $entity->setConsent(true);

        $violations = $this->validator->validate($entity);

        $this->assertGreaterThan(0, count($violations));
    }

    public function testValidationFailsForTooLongEmail(): void
    {
        $entity = new FormContactEntity();
        $entity->setName('John Doe');
        $entity->setEmailAddress(str_repeat('a', 191) . '@example.com'); // More than 200 characters
        $entity->setMessage('Valid message with more than 10 characters');
        $entity->setConsent(true);

        $violations = $this->validator->validate($entity);

        $this->assertGreaterThan(0, count($violations));
    }

    public function testValidationFailsForTooLongPhone(): void
    {
        $entity = new FormContactEntity();
        $entity->setName('John Doe');
        $entity->setEmailAddress('john@example.com');
        $entity->setPhone(str_repeat('1', 41)); // More than 40 characters
        $entity->setMessage('Valid message with more than 10 characters');
        $entity->setConsent(true);

        $violations = $this->validator->validate($entity);

        $this->assertGreaterThan(0, count($violations));
    }

    public function testGetMessageHtmlEscapesAndConvertsLineBreaks(): void
    {
        $entity = new FormContactEntity();
        $entity->setMessage("Line 1\nLine 2\n<script>alert('xss')</script>");

        $html = $entity->getMessageHtml();

        $this->assertStringContainsString('Line 1<br />', $html);
        $this->assertStringContainsString('Line 2<br />', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
        $this->assertStringNotContainsString('<script>', $html);
    }

    public function testGetEmailReturnsAddressObject(): void
    {
        $entity = new FormContactEntity();
        $entity->setName('John Doe');
        $entity->setEmailAddress('john@example.com');

        $address = $entity->getEmail();

        $this->assertInstanceOf(\Symfony\Component\Mime\Address::class, $address);
        $this->assertSame('john@example.com', $address->getAddress());
        $this->assertSame('John Doe', $address->getName());
    }
}
