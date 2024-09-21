<?php

namespace App\Entity;

use App\Repository\VisiteRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;


#[ORM\Entity(repositoryClass: VisiteRepository::class)]
class Visite
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['visite:read', 'reservation:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    // ICI FAIRE GAFFE AUX GROUPES PEUT ETRE UNIQUEMENT ID NECESSAIRE ET PAS ICI
    #[Groups(['visite:read', 'site:read', 'reservation:read'])]
    private ?string $name = null;

    #[ORM\Column]
    #[Groups(['visite:read'])]
    private ?float $duration = null;

    #[ORM\Column]
    #[Groups(['visite:read'])]
    private ?int $price = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Groups(['visite:read'])]
    private ?string $description = null;

    #[ORM\ManyToOne(inversedBy: 'visites')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['visite:read'])]
    private ?Site $site = null;

    /**
     * @var Collection<int, Reservation>
     */
    #[ORM\OneToMany(targetEntity: Reservation::class, mappedBy: 'visite')]
    private Collection $reservations;

    /**
     * @var Collection<int, User>
     */
    #[ORM\ManyToMany(targetEntity: User::class, inversedBy: 'visites')]
    private Collection $guide;

    public function __construct()
    {
        $this->reservations = new ArrayCollection();
        $this->guide = new ArrayCollection();
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

    public function getDuration(): ?float
    {
        return $this->duration;
    }

    public function setDuration(float $duration): static
    {
        $this->duration = $duration;

        return $this;
    }

    public function getPrice(): ?int
    {
        return $this->price;
    }

    public function setPrice(int $price): static
    {
        $this->price = $price;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getSite(): ?Site
    {
        return $this->site;
    }

    public function setSite(?Site $site): static
    {
        $this->site = $site;

        return $this;
    }

    /**
     * @return Collection<int, Reservation>
     */
    public function getReservations(): Collection
    {
        return $this->reservations;
    }

    public function addReservation(Reservation $reservation): static
    {
        if (!$this->reservations->contains($reservation)) {
            $this->reservations->add($reservation);
            $reservation->setVisite($this);
        }

        return $this;
    }

    public function removeReservation(Reservation $reservation): static
    {
        if ($this->reservations->removeElement($reservation)) {
            // set the owning side to null (unless already changed)
            if ($reservation->getVisite() === $this) {
                $reservation->setVisite(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, User>
     */
    public function getGuide(): Collection
    {
        return $this->guide;
    }

    public function addGuide(User $guide): static
    {
        if (!$this->guide->contains($guide)) {
            $this->guide->add($guide);
        }

        return $this;
    }

    public function removeGuide(User $guide): static
    {
        $this->guide->removeElement($guide);

        return $this;
    }
}
