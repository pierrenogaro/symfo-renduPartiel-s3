<?php

namespace App\Entity;

use App\Repository\ContributionRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: ContributionRepository::class)]
class Contribution
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['contribution:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Event::class, inversedBy: 'contributions')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Event $event = null;

    #[ORM\Column(length: 255)]
    #[Groups(['contribution:read', 'contribution:write'])]
    private ?string $suggestion = null;

    #[ORM\Column(length: 20)]
    #[Groups(['contribution:read'])]
    private ?string $status = 'pending';

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(['contribution:read'])]
    private ?User $takenBy = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $createdBy = null;

    #[ORM\Column(type: 'datetime')]
    #[Groups(['contribution:read'])]
    private ?\DateTimeInterface $createdAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEvent(): ?Event
    {
        return $this->event;
    }

    public function setEvent(?Event $event): self
    {
        $this->event = $event;

        return $this;
    }

    public function getSuggestion(): ?string
    {
        return $this->suggestion;
    }

    public function setSuggestion(string $suggestion): self
    {
        $this->suggestion = $suggestion;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $validStatuses = ['pending', 'taken'];
        if (!in_array($status, $validStatuses)) {
            throw new \InvalidArgumentException("Invalid status value");
        }

        $this->status = $status;

        return $this;
    }

    public function getTakenBy(): ?User
    {
        return $this->takenBy;
    }

    public function setTakenBy(?User $takenBy): self
    {
        $this->takenBy = $takenBy;

        return $this;
    }

    public function getCreatedBy(): ?User
    {
        return $this->createdBy;
    }

    public function setCreatedBy(User $createdBy): self
    {
        $this->createdBy = $createdBy;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }
}
