<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\ChatRepository;

#[ORM\Entity(repositoryClass: ChatRepository::class)]
#[ORM\Table(name: 'chats')]
class Chat
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $client_id = null;

    public function getClient_id(): ?int
    {
        return $this->client_id;
    }

    public function setClient_id(?int $client_id): self
    {
        $this->client_id = $client_id;
        return $this;
    }

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $support_id = null;

    public function getSupport_id(): ?int
    {
        return $this->support_id;
    }

    public function setSupport_id(?int $support_id): self
    {
        $this->support_id = $support_id;
        return $this;
    }

    public function getClientId(): ?int
    {
        return $this->client_id;
    }

    public function setClientId(?int $client_id): static
    {
        $this->client_id = $client_id;

        return $this;
    }

    public function getSupportId(): ?int
    {
        return $this->support_id;
    }

    public function setSupportId(?int $support_id): static
    {
        $this->support_id = $support_id;

        return $this;
    }

}
