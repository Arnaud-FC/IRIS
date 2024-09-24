<?php

namespace App\Entity;

use App\Repository\DecisionRepository;
use Doctrine\ORM\Mapping as ORM;
Use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\Groups;



#[ORM\Entity(repositoryClass: DecisionRepository::class)]
class Decision
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['decision:read'])]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255, nullable: false)]
    #[Assert\Choice(choices: ['accepted', 'waiting', 'denied'], message: 'statut non valide.')]
    #[Groups(['decision:read'])]
    private ?string $status = null;

    #[ORM\ManyToOne(inversedBy: 'decisions')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['decision:read'])]
    private ?User $guide = null;

    #[ORM\ManyToOne(inversedBy: 'decisions')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['decision:read'])]
    private ?Reservation $reservation = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getGuide(): ?User
    {
        return $this->guide;
    }

    public function setGuide(?User $guide): static
    {
        $this->guide = $guide;

        return $this;
    }

    public function getReservation(): ?Reservation
    {
        return $this->reservation;
    }

    public function setReservation(?Reservation $reservation): static
    {
        $this->reservation = $reservation;

        return $this;
    }
}
