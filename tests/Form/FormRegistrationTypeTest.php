<?php

declare(strict_types=1);

namespace App\Tests\Form;

use App\Entity\FormRegistrationEntity;
use App\Form\FormRegistrationType;
use Symfony\Component\Form\Test\TypeTestCase;

class FormRegistrationTypeTest extends TypeTestCase
{
    public function testSubmitValidDataMapsToEntity(): void
    {
        $data = [
            'name'         => 'Jane Doe',
            'email'        => 'jane@example.com',
            'phone'        => '+49 123 456789',
            'motivation'   => 'Ich liebe Theater.',
            'roleTypes'    => ['tragisch', 'böse'],
            'roleReason'   => 'Weil Schurken die besten Rollen sind.',
            'expectations' => 'Spaß und Gemeinschaft.',
            'consent'      => '1',
            'copy'         => '1',
        ];

        $entity = new FormRegistrationEntity();
        $form = $this->factory->create(FormRegistrationType::class, $entity, ['csrf_protection' => false]);

        $form->submit($data);

        $this->assertTrue($form->isSynchronized());
        $this->assertSame('Jane Doe', $entity->getName());
        $this->assertSame('jane@example.com', $entity->getEmailAddress());
        $this->assertSame('+49 123 456789', $entity->getPhone());
        $this->assertSame('Ich liebe Theater.', $entity->getMotivation());
        $this->assertSame(['tragisch', 'böse'], $entity->getRoleTypes());
        $this->assertSame('Weil Schurken die besten Rollen sind.', $entity->getRoleReason());
        $this->assertSame('Spaß und Gemeinschaft.', $entity->getExpectations());
        $this->assertTrue($entity->getConsent());
        $this->assertTrue($entity->getCopy());
    }

    public function testRoleTypesAreExpandedCheckboxes(): void
    {
        $form = $this->factory->create(FormRegistrationType::class, new FormRegistrationEntity(), ['csrf_protection' => false]);

        $config = $form->get('roleTypes')->getConfig();
        $this->assertTrue($config->getOption('multiple'));
        $this->assertTrue($config->getOption('expanded'));

        $choices = $config->getOption('choices');
        $this->assertSame(
            ['tragisch', 'komisch', 'lustig', 'dramatisch', 'böse'],
            array_values($choices)
        );
    }

    public function testFormHasHoneypotFields(): void
    {
        $form = $this->factory->create(FormRegistrationType::class, new FormRegistrationEntity(), ['csrf_protection' => false]);

        $this->assertTrue($form->has('emailrep'));
        $this->assertTrue($form->has('website'));
        $this->assertFalse($form->get('website')->getConfig()->getMapped());
    }

    public function testSubmitEmptyDataStaysSynchronized(): void
    {
        $form = $this->factory->create(FormRegistrationType::class, new FormRegistrationEntity(), ['csrf_protection' => false]);

        $form->submit([]);

        $this->assertTrue($form->isSynchronized());
    }
}
