<?php

namespace App\Entity;

use App\Repository\DecisionRepository;
use Doctrine\ORM\Mapping as ORM;
Use Symfony\Component\Validator\Constraints as Assert;


#[ORM\Entity(repositoryClass: DecisionRepository::class)]
class Decision
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255, nullable: false)]
    #[Assert\All([
        new Assert\Choice(choices: ['accepted', 'waiting', 'denied'], message: 'statut non valide.')
    ])]
    private ?string $status = null;

    #[ORM\ManyToOne(inversedBy: 'decisions')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $guide = null;

    #[ORM\ManyToOne(inversedBy: 'decisions')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Reservation $reservation = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function isStatus(): ?bool
    {
        return $this->status;
    }

    public function setStatus(bool $status): static
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
