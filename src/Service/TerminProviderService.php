<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\TerminEntity;
use App\Repository\TerminRepository;

/**
 * Provides the public dates shown on the /termine page (stored in the
 * database; maintainable via the upcoming admin area).
 */
class TerminProviderService
{
    public function __construct(private readonly TerminRepository $termine)
    {
    }

    /**
     * @return array<int, TerminEntity>
     */
    public function getUpcoming(): array
    {
        return $this->termine->findUpcoming();
    }

    /**
     * The next one-off event, e.g. for the homepage teaser.
     */
    public function getNext(): ?TerminEntity
    {
        return $this->termine->findNextEvent();
    }
}
