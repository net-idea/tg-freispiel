<?php
declare(strict_types=1);

namespace App\Entity;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Mime\Address;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: 'App\\Repository\\FormContactRepository')]
#[ORM\Table(name: 'form_contact')]
class FormContactEntity
{
    #[ORM\Column(type: 'string', length: 160)]
    #[Assert\NotBlank(message: 'Bitte geben Sie Ihren Namen an.')]
    #[Assert\Length(max: 120, maxMessage: 'Bitte verwenden Sie höchstens {{ limit }} Zeichen.')]
    protected string $name = '';

    #[ORM\Column(type: 'string', length: 200)]
    #[Assert\NotBlank(message: 'Bitte geben Sie Ihre E‑Mail‑Adresse an.')]
    #[Assert\Email(message: 'Bitte geben Sie eine gültige E‑Mail‑Adresse an.')]
    #[Assert\Length(max: 200, maxMessage: 'Bitte verwenden Sie höchstens {{ limit }} Zeichen.')]
    protected string $emailAddress = '';

    // Not persisted; convenience for emails
    protected ?Address $email = null;

    #[ORM\Column(type: 'string', length: 40, nullable: true)]
    #[Assert\Length(max: 40, maxMessage: 'Bitte verwenden Sie höchstens {{ limit }} Zeichen.')]
    protected string $phone = '';

    #[ORM\Column(type: 'boolean')]
    #[Assert\IsTrue(message: 'Bitte stimmen Sie der Datenverarbeitung zu.')]
    protected bool $consent = false;

    #[ORM\Column(type: 'text')]
    #[Assert\NotBlank(message: 'Bitte geben Sie eine Nachricht ein.')]
    #[Assert\Length(min: 10, max: 5000, minMessage: 'Bitte geben Sie mindestens {{ limit }} Zeichen ein.', maxMessage: 'Bitte verwenden Sie höchstens {{ limit }} Zeichen.')]
    protected string $message = '';

    #[ORM\Column(type: 'boolean')]
    protected bool $copy = true;

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

    public function setEmail(Address $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getEmail(): Address
    {
        if (!$this->email) {
            $this->email = new Address(
                $this->emailAddress,
                $this->name
            );
        }

        return $this->email;
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

    public function setConsent(bool $consent): self
    {
        $this->consent = $consent;

        return $this;
    }

    public function getConsent(): bool
    {
        return $this->consent;
    }

    public function setMessage($message): self
    {
        $this->message = (string) $message;

        return $this;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function setCopy(bool $copy): self
    {
        $this->copy = $copy;

        return $this;
    }

    public function getCopy(): bool
    {
        return $this->copy;
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
     * Returns the message formatted as safe HTML with line breaks.
     */
    public function getMessageHtml(): string
    {
        return nl2br(htmlentities($this->message, ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8'));
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
