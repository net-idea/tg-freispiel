<?php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\DateEntity;
use App\Entity\UserEntity;
use PHPUnit\Framework\TestCase;

class DateEntityTest extends TestCase
{
    public function testEntityCanBeCreated(): void
    {
        $date = new DateEntity();

        $this->assertNull($date->getId());
        $this->assertInstanceOf(\DateTimeImmutable::class, $date->getCreatedAt());
        $this->assertTrue($date->isActive());
        $this->assertSame(0, $date->getSortOrder());
    }

    public function testSettersAndGetters(): void
    {
        $startsAt = new \DateTimeImmutable('2026-09-12 10:30:00');
        $user = new UserEntity();

        $date = (new DateEntity())
            ->setTitle('Probestunde zum Reinschnuppern')
            ->setDescription('Komm einfach vorbei.')
            ->setStartsAt($startsAt)
            ->setRecurrence(null)
            ->setActive(true)
            ->setSortOrder(5)
            ->setCreatedBy($user);

        $this->assertSame('Probestunde zum Reinschnuppern', $date->getTitle());
        $this->assertSame('Komm einfach vorbei.', $date->getDescription());
        $this->assertSame($startsAt, $date->getStartsAt());
        $this->assertNull($date->getRecurrence());
        $this->assertTrue($date->isActive());
        $this->assertSame(5, $date->getSortOrder());
        $this->assertSame($user, $date->getCreatedBy());
    }

    public function testFormatGermanForOneOffDate(): void
    {
        $date = (new DateEntity())
            ->setTitle('Probestunde zum Reinschnuppern')
            ->setStartsAt(new \DateTimeImmutable('2026-09-12 10:30:00'));

        $this->assertSame('12. September 2026 um 10:30 Uhr', $date->formatGerman());
    }

    public function testFormatGermanForRecurringDate(): void
    {
        $date = (new DateEntity())
            ->setTitle('Proben')
            ->setRecurrence('jeden Dienstag um 19 Uhr (ca. 2–3 Stunden)');

        $this->assertSame('jeden Dienstag um 19 Uhr (ca. 2–3 Stunden)', $date->formatGerman());
    }
}
