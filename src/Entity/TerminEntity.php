<?php
declare(strict_types=1);

namespace App\Entity;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * A public date (Termin) shown on the /termine page. Either a one-off event
 * with a concrete start ($startsAt) or a recurring slot described in prose
 * ($recurrence, e.g. "jeden Dienstag um 19 Uhr").
 */
#[ORM\Entity(repositoryClass: 'App\\Repository\\TerminRepository')]
#[ORM\Table(name: 'termin')]
class TerminEntity
{
    private const array GERMAN_MONTHS = [
        1  => 'Januar',
        2  => 'Februar',
        3  => 'März',
        4  => 'April',
        5  => 'Mai',
        6  => 'Juni',
        7  => 'Juli',
        8  => 'August',
        9  => 'September',
        10 => 'Oktober',
        11 => 'November',
        12 => 'Dezember',
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 200)]
    #[Assert\NotBlank(message: 'Bitte gib einen Titel an.')]
    #[Assert\Length(max: 200, maxMessage: 'Bitte verwende höchstens {{ limit }} Zeichen.')]
    private string $title = '';

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    /**
     * Start of a one-off event; null for recurring slots.
     */
    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $startsAt = null;

    /**
     * Prose schedule for recurring slots, e.g. "jeden Dienstag um 19 Uhr (ca. 2–3 Stunden)".
     */
    #[ORM\Column(type: 'string', length: 200, nullable: true)]
    private ?string $recurrence = null;

    #[ORM\Column(type: 'boolean')]
    private bool $active = true;

    #[ORM\Column(type: 'integer')]
    private int $sortOrder = 0;

    #[ORM\ManyToOne(targetEntity: UserEntity::class)]
    #[ORM\JoinColumn(name: 'created_by_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?UserEntity $createdBy = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getStartsAt(): ?DateTimeImmutable
    {
        return $this->startsAt;
    }

    public function setStartsAt(?DateTimeImmutable $startsAt): self
    {
        $this->startsAt = $startsAt;

        return $this;
    }

    public function getRecurrence(): ?string
    {
        return $this->recurrence;
    }

    public function setRecurrence(?string $recurrence): self
    {
        $this->recurrence = $recurrence;

        return $this;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): self
    {
        $this->active = $active;

        return $this;
    }

    public function getSortOrder(): int
    {
        return $this->sortOrder;
    }

    public function setSortOrder(int $sortOrder): self
    {
        $this->sortOrder = $sortOrder;

        return $this;
    }

    public function getCreatedBy(): ?UserEntity
    {
        return $this->createdBy;
    }

    public function setCreatedBy(?UserEntity $createdBy): self
    {
        $this->createdBy = $createdBy;

        return $this;
    }

    /**
     * Locale-independent German display: the recurrence prose for recurring
     * slots, otherwise e.g. "12. September 2026 um 10:30 Uhr".
     */
    public function formatGerman(): string
    {
        if (null !== $this->recurrence && '' !== $this->recurrence) {
            return $this->recurrence;
        }

        if (null === $this->startsAt) {
            return '';
        }

        return sprintf(
            '%d. %s %d um %s Uhr',
            (int) $this->startsAt->format('j'),
            self::GERMAN_MONTHS[(int) $this->startsAt->format('n')],
            (int) $this->startsAt->format('Y'),
            $this->startsAt->format('H:i')
        );
    }
}
