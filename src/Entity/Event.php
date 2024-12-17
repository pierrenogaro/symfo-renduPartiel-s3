<?php

namespace App\Entity;

use App\Repository\EventRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

#[ORM\Entity(repositoryClass: EventRepository::class)]
class Event
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['event:read', 'invitation:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['event:read', 'event:write', 'invitation:read'])]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['event:read', 'event:write'])]
    private ?string $description = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'events')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['event:read'])]
    private ?User $author = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Groups(['event:read', 'event:write'])]
    private ?string $place = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Groups(['event:read', 'event:write'])]
    private ?\DateTimeInterface $startDate = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Groups(['event:read', 'event:write'])]
    private ?\DateTimeInterface $endDate = null;

    #[ORM\ManyToOne(inversedBy: 'organizedEvents')]
    #[Groups(['event:read', 'event:write'])]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $organizer = null;

    #[ORM\Column(type: Types::BOOLEAN)]
    #[Groups(['event:write'])]
    private ?bool $state = true;

    #[ORM\Column(type: Types::BOOLEAN)]
    #[Groups(['event:write'])]
    private bool $status = true;

    #[ORM\Column(length: 10)]
    #[Groups(['event:read', 'event:write'])]
    private ?string $typeOfPlace = null;

    #[ORM\ManyToMany(targetEntity: User::class, inversedBy: 'events')]
    private Collection $participants;

    #[ORM\OneToMany(mappedBy: 'event', targetEntity: Invitation::class, cascade: ['persist', 'remove'])]
    private Collection $invitations;

    #[ORM\OneToMany(mappedBy: 'event', targetEntity: Contribution::class, cascade: ['persist', 'remove'])]
    private Collection $contributions;

    public function __construct()
    {
        $this->participants = new ArrayCollection();
        $this->invitations = new ArrayCollection();
        $this->contributions = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getAuthor(): ?User
    {
        return $this->author;
    }

    public function setAuthor(?User $author): static
    {
        $this->author = $author;

        return $this;
    }

    public function getPlace(): ?string
    {
        return $this->place;
    }

    public function setPlace(string $place): static
    {
        $this->place = $place;

        return $this;
    }

    public function getStartDate(): ?\DateTimeInterface
    {
        return $this->startDate;
    }

    public function setStartDate(\DateTimeInterface $startDate): static
    {
        $this->startDate = $startDate;

        return $this;
    }

    public function getEndDate(): ?\DateTimeInterface
    {
        return $this->endDate;
    }

    public function setEndDate(\DateTimeInterface $endDate): static
    {
        $this->endDate = $endDate;

        return $this;
    }

    public function getOrganizer(): User
    {
        return $this->organizer;
    }

    public function setOrganizer(?User $organizer): self
    {
        $this->organizer = $organizer;

        return $this;
    }

    #[Groups(['event:read'])]
    public function getState(): string
    {
        return $this->state ? 'on schedule' : 'canceled';
    }

    public function setState(bool $state): self
    {
        $this->state = $state;
        return $this;
    }

    #[Groups(['event:read'])]
    public function getStatus(): string
    {
        return $this->status ? 'public' : 'private';
    }

    public function setStatus(bool $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getTypeOfPlace(): ?string
    {
        return $this->typeOfPlace;
    }

    public function setTypeOfPlace(string $typeOfPlace): static
    {
        $this->typeOfPlace = $typeOfPlace;
        return $this;
    }

    public function getParticipants(): Collection
    {
        return $this->participants;
    }

    public function addParticipant(User $user): static
    {
        if (!$this->participants->contains($user)) {
            $this->participants->add($user);
        }
        return $this;
    }

    public function removeParticipant(User $user): static
    {
        $this->participants->removeElement($user);
        return $this;
    }

    public function getInvitations(): Collection
    {
        return $this->invitations;
    }

    public function addInvitation(Invitation $invitation): self
    {
        if (!$this->invitations->contains($invitation)) {
            $this->invitations->add($invitation);
            $invitation->setEvent($this);
        }
        return $this;
    }

    public function removeInvitation(Invitation $invitation): self
    {
        if ($this->invitations->removeElement($invitation)) {
            if ($invitation->getEvent() === $this) {
                $invitation->setEvent(null);
            }
        }
        return $this;
    }

    public function getContributions(): Collection
    {
        return $this->contributions;
    }
}
