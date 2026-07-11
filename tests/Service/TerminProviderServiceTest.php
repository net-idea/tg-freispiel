<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\TerminEntity;
use App\Repository\TerminRepository;
use App\Service\TerminProviderService;
use PHPUnit\Framework\TestCase;

class TerminProviderServiceTest extends TestCase
{
    public function testGetUpcomingDelegatesToRepository(): void
    {
        $termin = (new TerminEntity())->setTitle('Probestunde zum Reinschnuppern');

        $repo = $this->createMock(TerminRepository::class);
        $repo->expects($this->once())->method('findUpcoming')->willReturn([$termin]);

        $provider = new TerminProviderService($repo);

        $this->assertSame([$termin], $provider->getUpcoming());
    }

    public function testGetNextDelegatesToRepository(): void
    {
        $termin = (new TerminEntity())->setTitle('Probestunde zum Reinschnuppern');

        $repo = $this->createMock(TerminRepository::class);
        $repo->expects($this->once())->method('findNextEvent')->willReturn($termin);

        $provider = new TerminProviderService($repo);

        $this->assertSame($termin, $provider->getNext());
    }
}
