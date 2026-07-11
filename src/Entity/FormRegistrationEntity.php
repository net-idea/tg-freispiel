<?php
declare(strict_types=1);

namespace App\Entity;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Anmeldung zur Probestunde (Casting-Registrierung).
 */
#[ORM\Entity(repositoryClass: 'App\\Repository\\FormRegistrationRepository')]
#[ORM\Table(name: 'form_registration')]
class FormRegistrationEntity
{
    public const array ROLE_TYPES = ['tragisch', 'komisch', 'lustig', 'dramatisch', 'böse'];

    #[ORM\Column(type: 'string', length: 160)]
    #[Assert\NotBlank(message: 'Bitte gib deinen Namen an.')]
    #[Assert\Length(max: 120, maxMessage: 'Bitte verwende höchstens {{ limit }} Zeichen.')]
    protected string $name = '';

    #[ORM\Column(type: 'string', length: 200)]
    #[Assert\NotBlank(message: 'Bitte gib deine E‑Mail‑Adresse an.')]
    #[Assert\Email(message: 'Bitte gib eine gültige E‑Mail‑Adresse an.')]
    #[Assert\Length(max: 200, maxMessage: 'Bitte verwende höchstens {{ limit }} Zeichen.')]
    protected string $emailAddress = '';

    #[ORM\Column(type: 'string', length: 40)]
    #[Assert\NotBlank(message: 'Bitte gib deine Telefonnummer an.')]
    #[Assert\Length(max: 40, maxMessage: 'Bitte verwende höchstens {{ limit }} Zeichen.')]
    protected string $phone = '';

    #[ORM\Column(type: 'text')]
    #[Assert\NotBlank(message: 'Bitte beantworte diese Frage.')]
    #[Assert\Length(max: 2000, maxMessage: 'Bitte verwende höchstens {{ limit }} Zeichen.')]
    protected string $motivation = '';

    /**
     * @var array<int, string>
     */
    #[ORM\Column(type: 'json')]
    #[Assert\Count(min: 1, minMessage: 'Bitte wähle mindestens eine Rolle aus.')]
    #[Assert\Choice(choices: self::ROLE_TYPES, multiple: true, multipleMessage: 'Bitte wähle nur aus den angebotenen Rollen.')]
    protected array $roleTypes = [];

    #[ORM\Column(type: 'text')]
    #[Assert\NotBlank(message: 'Bitte beantworte diese Frage.')]
    #[Assert\Length(max: 2000, maxMessage: 'Bitte verwende höchstens {{ limit }} Zeichen.')]
    protected string $roleReason = '';

    #[ORM\Column(type: 'text')]
    #[Assert\NotBlank(message: 'Bitte beantworte diese Frage.')]
    #[Assert\Length(max: 2000, maxMessage: 'Bitte verwende höchstens {{ limit }} Zeichen.')]
    protected string $expectations = '';

    #[ORM\Column(type: 'boolean')]
    #[Assert\IsTrue(message: 'Bitte stimme der Datenverarbeitung zu.')]
    protected bool $consent = false;

    // Honeypot; not persisted
    protected string $emailrep = '';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $createdAt;

    #[ORM\OneToOne(targetEntity: FormSubmissionMetaEntity::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\JoinColumn(name: 'meta_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?FormSubmissionMetaEntity $meta = null;

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

    public function setName($name): self
    {
        $this->name = (string) $name;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setEmailAddress(string $emailAddress): self
    {
        $this->emailAddress = $emailAddress;

        return $this;
    }

    public function getEmailAddress(): string
    {
        return $this->emailAddress;
    }

    public function setPhone($phone): self
    {
        $this->phone = (string) $phone;

        return $this;
    }

    public function getPhone(): string
    {
        return $this->phone;
    }

    public function setMotivation($motivation): self
    {
        $this->motivation = (string) $motivation;

        return $this;
    }

    public function getMotivation(): string
    {
        return $this->motivation;
    }

    /**
     * @param array<int, string> $roleTypes
     */
    public function setRoleTypes(array $roleTypes): self
    {
        $this->roleTypes = array_values($roleTypes);

        return $this;
    }

    /**
     * @return array<int, string>
     */
    public function getRoleTypes(): array
    {
        return $this->roleTypes;
    }

    public function setRoleReason($roleReason): self
    {
        $this->roleReason = (string) $roleReason;

        return $this;
    }

    public function getRoleReason(): string
    {
        return $this->roleReason;
    }

    public function setExpectations($expectations): self
    {
        $this->expectations = (string) $expectations;

        return $this;
    }

    public function getExpectations(): string
    {
        return $this->expectations;
    }

    public function setConsent(bool $consent): self
    {
        $this->consent = $consent;

        return $this;
    }

    public function getConsent(): bool
    {
        return $this->consent;
    }

    public function setEmailrep($emailrep): self
    {
        $this->emailrep = (string) $emailrep;

        return $this;
    }

    public function getEmailrep(): string
    {
        return $this->emailrep;
    }

    /**
     * Set meta info object.
     */
    public function setMeta(FormSubmissionMetaEntity $meta): self
    {
        $this->meta = $meta;

        return $this;
    }

    /**
     * Get meta info object (never null; returns empty object if not set).
     */
    public function getMeta(): FormSubmissionMetaEntity
    {
        if (null === $this->meta) {
            $this->meta = new FormSubmissionMetaEntity();
        }

        return $this->meta;
    }
}
