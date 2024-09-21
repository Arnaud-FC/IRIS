<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Annotation\Groups;
Use Symfony\Component\Validator\Constraints as Assert;


#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['user:read', 'reservation:read', 'decision:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    #[Groups(['user:read'])]
    #[Assert\NotBlank(message: 'l\'email ne peut etre vide')]
    #[Assert\Email(message: 'l\'email n\'est pas valide')]
    private ?string $email = null;

    /**
     * @var list<string> The user roles
     */
    #[ORM\Column]
    #[Groups(['user:read'])]
    // #[Assert\All([
    //     new Assert\Choice(choices: ['ROLE_USER', 'ROLE_GUIDE'], message: 'Rôle non valide.')
    // ])]
    private array $roles = [];

    

    /**
     * @var string The hashed password
     */
    #[ORM\Column]
    #[Assert\NotBlank(message: 'le password ne peut etre vide')]
    #[Assert\Length(min:6, minMessage: 'le password ne peut etre vide')]
    private ?string $password = null;

    #[ORM\Column(length: 255)]
    #[Groups(['decision:read'])]
    private ?string $lastName = null;

    #[ORM\Column(length: 255)]
    #[Groups(['decision:read'])]
    private ?string $firstName = null;

    #[ORM\Column(length: 1000, nullable: true)]
    private ?string $picture = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    /**
     * @var Collection<int, Notification>
     */
    #[ORM\OneToMany(targetEntity: Notification::class, mappedBy: 'user', orphanRemoval: true)]
    private Collection $notifications;

    /**
     * @var Collection<int, Chat>
     */
    #[ORM\OneToMany(targetEntity: Chat::class, mappedBy: 'receiver')]
    private Collection $chats;

    /**
     * @var Collection<int, Chat>
     */
    #[ORM\OneToMany(targetEntity: Chat::class, mappedBy: 'sender')]
    private Collection $senderChats;

    /**
     * @var Collection<int, Message>
     */
    #[ORM\OneToMany(targetEntity: Message::class, mappedBy: 'sender')]
    private Collection $sender_messages;

    /**
     * @var Collection<int, Billing>
     */
    #[ORM\OneToMany(targetEntity: Billing::class, mappedBy: 'user')]
    private Collection $billings;

    /**
     * @var Collection<int, LanguagesAvailable>
     */
    #[ORM\ManyToMany(targetEntity: LanguagesAvailable::class, mappedBy: 'user')]
    private Collection $languagesAvailables;

    /**
     * @var Collection<int, Visite>
     */
    #[ORM\ManyToMany(targetEntity: Visite::class, mappedBy: 'guide')]
    private Collection $visites;

    /**
     * @var Collection<int, Reservation>
     */
    #[ORM\OneToMany(targetEntity: Reservation::class, mappedBy: 'custommer')]
    private Collection $reservations;

    /**
     * @var Collection<int, Decision>
     */
    #[ORM\OneToMany(targetEntity: Decision::class, mappedBy: 'guide', orphanRemoval: true)]
    private Collection $decisions;

    /**
     * @var Collection<int, Reservation>
     */
    #[ORM\OneToMany(targetEntity: Reservation::class, mappedBy: 'guide')]
    private Collection $reservations_guide;

    public function __construct()
    {
        $this->notifications = new ArrayCollection();
        $this->chats = new ArrayCollection();
        $this->senderChats = new ArrayCollection();
        $this->sender_messages = new ArrayCollection();
        $this->billings = new ArrayCollection();
        $this->languagesAvailables = new ArrayCollection();
        $this->visites = new ArrayCollection();
        $this->reservations = new ArrayCollection();
        $this->decisions = new ArrayCollection();
        $this->reservations_guide = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     *
     * @return list<string>
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        // $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * @param string $roles
     */
    public function setRoles(string $roles): static
    {
        $this->roles = [$roles];

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): static
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): static
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getPicture(): ?string
    {
        return $this->picture;
    }

    public function setPicture(?string $picture): static
    {
        $this->picture = $picture;

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

    /**
     * @return Collection<int, Notification>
     */
    public function getNotifications(): Collection
    {
        return $this->notifications;
    }

    public function addNotification(Notification $notification): static
    {
        if (!$this->notifications->contains($notification)) {
            $this->notifications->add($notification);
            $notification->setUser($this);
        }

        return $this;
    }

    public function removeNotification(Notification $notification): static
    {
        if ($this->notifications->removeElement($notification)) {
            // set the owning side to null (unless already changed)
            if ($notification->getUser() === $this) {
                $notification->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Chat>
     */
    public function getChats(): Collection
    {
        return $this->chats;
    }

    public function addChat(Chat $chat): static
    {
        if (!$this->chats->contains($chat)) {
            $this->chats->add($chat);
            $chat->setReceiver($this);
        }

        return $this;
    }

    public function removeChat(Chat $chat): static
    {
        if ($this->chats->removeElement($chat)) {
            // set the owning side to null (unless already changed)
            if ($chat->getReceiver() === $this) {
                $chat->setReceiver(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Chat>
     */
    public function getSenderChats(): Collection
    {
        return $this->senderChats;
    }

    public function addSenderChat(Chat $senderChat): static
    {
        if (!$this->senderChats->contains($senderChat)) {
            $this->senderChats->add($senderChat);
            $senderChat->setSender($this);
        }

        return $this;
    }

    public function removeSenderChat(Chat $senderChat): static
    {
        if ($this->senderChats->removeElement($senderChat)) {
            // set the owning side to null (unless already changed)
            if ($senderChat->getSender() === $this) {
                $senderChat->setSender(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Message>
     */
    public function getSenderMessages(): Collection
    {
        return $this->sender_messages;
    }

    public function addSenderMessage(Message $senderMessage): static
    {
        if (!$this->sender_messages->contains($senderMessage)) {
            $this->sender_messages->add($senderMessage);
            $senderMessage->setSender($this);
        }

        return $this;
    }

    public function removeSenderMessage(Message $senderMessage): static
    {
        if ($this->sender_messages->removeElement($senderMessage)) {
            // set the owning side to null (unless already changed)
            if ($senderMessage->getSender() === $this) {
                $senderMessage->setSender(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Billing>
     */
    public function getBillings(): Collection
    {
        return $this->billings;
    }

    public function addBilling(Billing $billing): static
    {
        if (!$this->billings->contains($billing)) {
            $this->billings->add($billing);
            $billing->setUser($this);
        }

        return $this;
    }

    public function removeBilling(Billing $billing): static
    {
        if ($this->billings->removeElement($billing)) {
            // set the owning side to null (unless already changed)
            if ($billing->getUser() === $this) {
                $billing->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, LanguagesAvailable>
     */
    public function getLanguagesAvailables(): Collection
    {
        return $this->languagesAvailables;
    }

    public function addLanguagesAvailable(LanguagesAvailable $languagesAvailable): static
    {
        if (!$this->languagesAvailables->contains($languagesAvailable)) {
            $this->languagesAvailables->add($languagesAvailable);
            $languagesAvailable->addUser($this);
        }

        return $this;
    }

    public function removeLanguagesAvailable(LanguagesAvailable $languagesAvailable): static
    {
        if ($this->languagesAvailables->removeElement($languagesAvailable)) {
            $languagesAvailable->removeUser($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, Visite>
     */
    public function getVisites(): Collection
    {
        return $this->visites;
    }

    public function addVisite(Visite $visite): static
    {
        if (!$this->visites->contains($visite)) {
            $this->visites->add($visite);
            $visite->addGuide($this);
        }

        return $this;
    }

    public function removeVisite(Visite $visite): static
    {
        if ($this->visites->removeElement($visite)) {
            $visite->removeGuide($this);
        }

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
            $reservation->setCustommer($this);
        }

        return $this;
    }

    public function removeReservation(Reservation $reservation): static
    {
        if ($this->reservations->removeElement($reservation)) {
            // set the owning side to null (unless already changed)
            if ($reservation->getCustommer() === $this) {
                $reservation->setCustommer(null);
            }
        }

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
            $decision->setGuide($this);
        }

        return $this;
    }

    public function removeDecision(Decision $decision): static
    {
        if ($this->decisions->removeElement($decision)) {
            // set the owning side to null (unless already changed)
            if ($decision->getGuide() === $this) {
                $decision->setGuide(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Reservation>
     */
    public function getReservationsGuide(): Collection
    {
        return $this->reservations_guide;
    }

    public function addReservationsGuide(Reservation $reservationsGuide): static
    {
        if (!$this->reservations_guide->contains($reservationsGuide)) {
            $this->reservations_guide->add($reservationsGuide);
            $reservationsGuide->setGuide($this);
        }

        return $this;
    }

    public function removeReservationsGuide(Reservation $reservationsGuide): static
    {
        if ($this->reservations_guide->removeElement($reservationsGuide)) {
            // set the owning side to null (unless already changed)
            if ($reservationsGuide->getGuide() === $this) {
                $reservationsGuide->setGuide(null);
            }
        }

        return $this;
    }
}
