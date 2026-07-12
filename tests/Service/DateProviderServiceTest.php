<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\DateEntity;
use App\Repository\DateRepository;
use App\Service\DateProviderService;
use PHPUnit\Framework\TestCase;

class DateProviderServiceTest extends TestCase
{
    public function testGetUpcomingDelegatesToRepository(): void
    {
        $date = (new DateEntity())->setTitle('Probestunde zum Reinschnuppern');

        $repo = $this->createMock(DateRepository::class);
        $repo->expects($this->once())->method('findUpcoming')->willReturn([$date]);

        $provider = new DateProviderService($repo);

        $this->assertSame([$date], $provider->getUpcoming());
    }

    public function testGetNextDelegatesToRepository(): void
    {
        $date = (new DateEntity())->setTitle('Probestunde zum Reinschnuppern');

        $repo = $this->createMock(DateRepository::class);
        $repo->expects($this->once())->method('findNext')->willReturn($date);

        $provider = new DateProviderService($repo);

        $this->assertSame($date, $provider->getNext());
    }
}
