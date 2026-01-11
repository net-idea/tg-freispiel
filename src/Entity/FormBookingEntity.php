<?php
declare(strict_types=1);

namespace App\Entity;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

/**
 * Minimal stub entity for booking-related features.
 * Marked as @codeCoverageIgnore in prompts previously; keep minimal implementation.
 */
#[ORM\Entity]
#[ORM\Table(name: 'form_booking')]
class FormBookingEntity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 200)]
    private string $email = '';

    #[ORM\Column(type: 'string', length: 160)]
    private string $name = '';

    #[ORM\Column(type: 'string', length: 64)]
    private string $confirmationToken = '';

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new DateTimeImmutable();
        // generate a small random token for stubbing
        $this->confirmationToken = bin2hex(random_bytes(8));
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getConfirmationToken(): string
    {
        return $this->confirmationToken;
    }

    public function setConfirmationToken(string $token): self
    {
        $this->confirmationToken = $token;

        return $this;
    }
}
