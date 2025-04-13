<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\RefundRepository;

#[ORM\Entity(repositoryClass: RefundRepository::class)]
#[ORM\Table(name: 'refund')]
class Refund
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $transactionId = null;

    public function getTransactionId(): ?int
    {
        return $this->transactionId;
    }

    public function setTransactionId(int $transactionId): self
    {
        $this->transactionId = $transactionId;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $Approved = null;

    public function getApproved(): ?string
    {
        return $this->Approved;
    }

    public function setApproved(?string $Approved): self
    {
        $this->Approved = $Approved;
        return $this;
    }

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $TimeApproved = null;

    public function getTimeApproved(): ?\DateTimeInterface
    {
        return $this->TimeApproved;
    }

    public function setTimeApproved(?\DateTimeInterface $TimeApproved): self
    {
        $this->TimeApproved = $TimeApproved;
        return $this;
    }

    #[ORM\Column(type: 'integer', nullable: false)]
    private ?int $months = null;

    public function getMonths(): ?int
    {
        return $this->months;
    }

    public function setMonths(int $months): self
    {
        $this->months = $months;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $reason = null;

    public function getReason(): ?string
    {
        return $this->reason;
    }

    public function setReason(?string $reason): self
    {
        $this->reason = $reason;
        return $this;
    }

    #[ORM\ManyToOne(targetEntity: Client::class, inversedBy: 'refunds')]
    #[ORM\JoinColumn(name: 'client_id', referencedColumnName: 'id')]
    private ?Client $client = null;

    public function getClient(): ?Client
    {
        return $this->client;
    }

    public function setClient(?Client $client): self
    {
        $this->client = $client;
        return $this;
    }

}
