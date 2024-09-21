<?php

namespace App\Entity;

use App\Repository\ReservationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;


#[ORM\Entity(repositoryClass: ReservationRepository::class)]
class Reservation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['visite:read', 'decision:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['visite:read', 'reservation:read'])]
    // METTRE LES 3 CHOIX waiting accepted denied
    private ?string $status = null;

    #[ORM\ManyToOne(inversedBy: 'reservations')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['visite:read', 'reservation:read'])]
    private ?Visite $visite = null;

    #[ORM\OneToOne(inversedBy: 'reservation', cascade: ['persist', 'remove'])]
    #[Groups(['visite:read', 'reservation:read'])]
    private ?Billing $billing = null;

    #[ORM\ManyToOne(inversedBy: 'reservations')]
    #[ORM\JoinColumn(nullable: true, onDelete: "SET NULL")]
    #[Groups(['reservation:read'])]
    private ?User $custommer = null;

    /**
     * @var Collection<int, Decision>
     */
    #[ORM\OneToMany(targetEntity: Decision::class, mappedBy: 'reservation', orphanRemoval: true)]
    private Collection $decisions;

    #[ORM\ManyToOne(inversedBy: 'reservations_guide')]
    private ?User $guide = null;

    public function __construct()
    {
        $this->decisions = new ArrayCollection();
    }

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

    public function getVisite(): ?Visite
    {
        return $this->visite;
    }

    public function setVisite(?Visite $visite): static
    {
        $this->visite = $visite;

        return $this;
    }

    public function getBilling(): ?Billing
    {
        return $this->billing;
    }

    public function setBilling(?Billing $billing): static
    {
        $this->billing = $billing;

        return $this;
    }

    public function getCustommer(): ?User
    {
        return $this->custommer;
    }

    public function setCustommer(?User $custommer): static
    {
        $this->custommer = $custommer;

        return $this;
    }

    /**
     * @return Collection<int, Decision>
     */
    public function getDecisions(): Collection
    {
        return $this->decisions;
    }

    public function addDecision(Decision $decision): static
    {
        if (!$this->decisions->contains($decision)) {
            $this->decisions->add($decision);
            $decision->setReservation($this);
        }

        return $this;
    }

    public function removeDecision(Decision $decision): static
    {
        if ($this->decisions->removeElement($decision)) {
            // set the owning side to null (unless already changed)
            if ($decision->getReservation() === $this) {
                $decision->setReservation(null);
            }
        }

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
}
