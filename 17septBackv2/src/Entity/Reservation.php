<?php

namespace App\Entity;

use App\Repository\ReservationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ReservationRepository::class)]
class Reservation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $status = null;

    #[ORM\ManyToOne(inversedBy: 'reservations')]
    #[ORM\JoinColumn(nullable: false)]
    private ?visite $visite = null;

    #[ORM\OneToOne(inversedBy: 'reservation', cascade: ['persist', 'remove'])]
    private ?billing $billing = null;

    #[ORM\ManyToOne(inversedBy: 'reservations')]
    #[ORM\JoinColumn(nullable: true, onDelete: "SET NULL")]
    private ?user $custommer = null;

    /**
     * @var Collection<int, Decision>
     */
    #[ORM\OneToMany(targetEntity: Decision::class, mappedBy: 'reservation', orphanRemoval: true)]
    private Collection $decisions;

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

    public function getVisite(): ?visite
    {
        return $this->visite;
    }

    public function setVisite(?visite $visite): static
    {
        $this->visite = $visite;

        return $this;
    }

    public function getBilling(): ?billing
    {
        return $this->billing;
    }

    public function setBilling(?billing $billing): static
    {
        $this->billing = $billing;

        return $this;
    }

    public function getCustommer(): ?user
    {
        return $this->custommer;
    }

    public function setCustommer(?user $custommer): static
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
}
