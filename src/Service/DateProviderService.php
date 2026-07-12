<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\DateEntity;
use App\Repository\DateRepository;

/**
 * Provides the public dates shown on the /termine page (stored in the
 * database; maintainable via the upcoming admin area).
 */
class DateProviderService
{
    public function __construct(private readonly DateRepository $dates)
    {
    }

    /**
     * @return array<int, DateEntity>
     */
    public function getUpcoming(): array
    {
        return $this->dates->findUpcoming();
    }

    /**
     * The next one-off date, e.g. for the homepage teaser.
     */
    public function getNext(): ?DateEntity
    {
        return $this->dates->findNext();
    }
}
