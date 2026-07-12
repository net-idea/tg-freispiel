<?php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\FormRegistrationEntity;
use App\Entity\FormSubmissionMetaEntity;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class FormRegistrationEntityTest extends TestCase
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
        $entity = new FormRegistrationEntity();

        $this->assertNull($entity->getId());
        $this->assertInstanceOf(\DateTimeImmutable::class, $entity->getCreatedAt());
    }

    public function testSettersAndGetters(): void
    {
        $entity = new FormRegistrationEntity();

        $entity->setName('Jane Doe');
        $this->assertSame('Jane Doe', $entity->getName());

        $entity->setEmailAddress('jane@example.com');
        $this->assertSame('jane@example.com', $entity->getEmailAddress());

        $entity->setPhone('+49 123 456789');
        $this->assertSame('+49 123 456789', $entity->getPhone());

        $entity->setMotivation('Ich liebe Theater.');
        $this->assertSame('Ich liebe Theater.', $entity->getMotivation());

        $entity->setRoleTypes(['tragisch', 'komisch']);
        $this->assertSame(['tragisch', 'komisch'], $entity->getRoleTypes());

        $entity->setRoleReason('Weil ich gerne Menschen zum Lachen bringe.');
        $this->assertSame('Weil ich gerne Menschen zum Lachen bringe.', $entity->getRoleReason());

        $entity->setExpectations('Spaß und eine tolle Gruppe.');
        $this->assertSame('Spaß und eine tolle Gruppe.', $entity->getExpectations());

        $entity->setConsent(true);
        $this->assertTrue($entity->getConsent());

        $this->assertTrue($entity->getCopy(), 'copy defaults to true');
        $entity->setCopy(false);
        $this->assertFalse($entity->getCopy());

        $entity->setEmailrep('spam@example.com');
        $this->assertSame('spam@example.com', $entity->getEmailrep());
    }

    public function testMetaIsNeverNull(): void
    {
        $entity = new FormRegistrationEntity();
        $this->assertInstanceOf(FormSubmissionMetaEntity::class, $entity->getMeta());

        $meta = new FormSubmissionMetaEntity();
        $entity->setMeta($meta);
        $this->assertSame($meta, $entity->getMeta());
    }

    public function testValidEntityHasNoViolations(): void
    {
        $entity = $this->makeValidEntity();

        $violations = $this->validator->validate($entity);

        $this->assertCount(0, $violations);
    }

    public function testValidationFailsForEmptyRequiredFields(): void
    {
        $entity = new FormRegistrationEntity();

        $violations = $this->validator->validate($entity);
        $paths = array_map(
            static fn ($v) => $v->getPropertyPath(),
            iterator_to_array($violations)
        );

        $this->assertContains('name', $paths);
        $this->assertContains('emailAddress', $paths);
        $this->assertContains('phone', $paths);
        $this->assertContains('motivation', $paths);
        $this->assertContains('roleTypes', $paths);
        $this->assertContains('roleReason', $paths);
        $this->assertContains('expectations', $paths);
        $this->assertContains('consent', $paths);
    }

    public function testValidationFailsForInvalidEmail(): void
    {
        $entity = $this->makeValidEntity();
        $entity->setEmailAddress('not-an-email');

        $violations = $this->validator->validate($entity);

        $this->assertGreaterThan(0, count($violations));
        $this->assertSame('emailAddress', $violations[0]->getPropertyPath());
    }

    public function testValidationFailsForUnknownRoleType(): void
    {
        $entity = $this->makeValidEntity();
        $entity->setRoleTypes(['heldenhaft']);

        $violations = $this->validator->validate($entity);

        $this->assertGreaterThan(0, count($violations));
        $this->assertSame('roleTypes', $violations[0]->getPropertyPath());
    }

    public function testValidationFailsWithoutConsent(): void
    {
        $entity = $this->makeValidEntity();
        $entity->setConsent(false);

        $violations = $this->validator->validate($entity);

        $this->assertGreaterThan(0, count($violations));
        $this->assertSame('consent', $violations[0]->getPropertyPath());
    }

    private function makeValidEntity(): FormRegistrationEntity
    {
        return (new FormRegistrationEntity())
            ->setName('Jane Doe')
            ->setEmailAddress('jane@example.com')
            ->setPhone('+49 123 456789')
            ->setMotivation('Ich liebe Theater und möchte neue Leute kennenlernen.')
            ->setRoleTypes(['komisch'])
            ->setRoleReason('Weil ich gerne Menschen zum Lachen bringe.')
            ->setExpectations('Eine offene Gruppe und regelmäßige Proben.')
            ->setConsent(true);
    }
}
