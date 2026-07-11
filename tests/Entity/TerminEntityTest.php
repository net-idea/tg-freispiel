<?php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\TerminEntity;
use App\Entity\UserEntity;
use PHPUnit\Framework\TestCase;

class TerminEntityTest extends TestCase
{
    public function testEntityCanBeCreated(): void
    {
        $termin = new TerminEntity();

        $this->assertNull($termin->getId());
        $this->assertInstanceOf(\DateTimeImmutable::class, $termin->getCreatedAt());
        $this->assertTrue($termin->isActive());
        $this->assertSame(0, $termin->getSortOrder());
    }

    public function testSettersAndGetters(): void
    {
        $startsAt = new \DateTimeImmutable('2026-09-12 10:30:00');
        $user = new UserEntity();

        $termin = (new TerminEntity())
            ->setTitle('Probestunde zum Reinschnuppern')
            ->setDescription('Komm einfach vorbei.')
            ->setStartsAt($startsAt)
            ->setRecurrence(null)
            ->setActive(true)
            ->setSortOrder(5)
            ->setCreatedBy($user);

        $this->assertSame('Probestunde zum Reinschnuppern', $termin->getTitle());
        $this->assertSame('Komm einfach vorbei.', $termin->getDescription());
        $this->assertSame($startsAt, $termin->getStartsAt());
        $this->assertNull($termin->getRecurrence());
        $this->assertTrue($termin->isActive());
        $this->assertSame(5, $termin->getSortOrder());
        $this->assertSame($user, $termin->getCreatedBy());
    }

    public function testFormatGermanForOneOffDate(): void
    {
        $termin = (new TerminEntity())
            ->setTitle('Probestunde zum Reinschnuppern')
            ->setStartsAt(new \DateTimeImmutable('2026-09-12 10:30:00'));

        $this->assertSame('12. September 2026 um 10:30 Uhr', $termin->formatGerman());
    }

    public function testFormatGermanForRecurringTermin(): void
    {
        $termin = (new TerminEntity())
            ->setTitle('Proben')
            ->setRecurrence('jeden Dienstag um 19 Uhr (ca. 2–3 Stunden)');

        $this->assertSame('jeden Dienstag um 19 Uhr (ca. 2–3 Stunden)', $termin->formatGerman());
    }
}
