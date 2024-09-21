<?php

namespace App\Entity;

use App\Repository\BillingRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BillingRepository::class)]
class Billing
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?bool $payed = null;

    #[ORM\ManyToOne(inversedBy: 'billings')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column]
    private ?float $amount = null;

    #[ORM\OneToOne(mappedBy: 'billing', cascade: ['persist', 'remove'])]
    private ?Reservation $reservation = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function isPayed(): ?bool
    {
        return $this->payed;
    }

    public function setPayed(bool $payed): static
    {
        $this->payed = $payed;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getAmount(): ?float
    {
        return $this->amount;
    }

    public function setAmount(float $amount): static
    {
        $this->amount = $amount;

        return $this;
    }

    public function getReservation(): ?Reservation
    {
        return $this->reservation;
    }

    public function setReservation(?Reservation $reservation): static
    {
        // unset the owning side of the relation if necessary
        if ($reservation === null && $this->reservation !== null) {
            $this->reservation->setBilling(null);
        }

        // set the owning side of the relation if necessary
        if ($reservation !== null && $reservation->getBilling() !== $this) {
            $reservation->setBilling($this);
        }

        $this->reservation = $reservation;

        return $this;
    }
}
