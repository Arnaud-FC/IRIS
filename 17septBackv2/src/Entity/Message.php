<?php

namespace App\Entity;

use App\Repository\MessageRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MessageRepository::class)]
class Message
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?bool $open = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $message = null;

    #[ORM\ManyToOne(inversedBy: 'sender_messages')]
    private ?user $sender = null;

    #[ORM\ManyToOne]
    private ?user $receiver = null;

    #[ORM\ManyToOne(inversedBy: 'messages')]
    #[ORM\JoinColumn(nullable: false)]
    private ?chat $chat = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function isOpen(): ?bool
    {
        return $this->open;
    }

    public function setOpen(bool $open): static
    {
        $this->open = $open;

        return $this;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(string $message): static
    {
        $this->message = $message;

        return $this;
    }

    public function getSender(): ?user
    {
        return $this->sender;
    }

    public function setSender(?user $sender): static
    {
        $this->sender = $sender;

        return $this;
    }

    public function getReceiver(): ?user
    {
        return $this->receiver;
    }

    public function setReceiver(?user $receiver): static
    {
        $this->receiver = $receiver;

        return $this;
    }

    public function getChat(): ?chat
    {
        return $this->chat;
    }

    public function setChat(?chat $chat): static
    {
        $this->chat = $chat;

        return $this;
    }
}
