<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\DataRepository;

#[ORM\Entity(repositoryClass: DataRepository::class)]
#[ORM\Table(name: 'data')]
class Data
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

    #[ORM\Column(type: 'decimal', nullable: false)]
    private ?float $amount = null;

    public function getAmount(): ?float
    {
        return $this->amount;
    }

    public function setAmount(float $amount): self
    {
        $this->amount = $amount;
        return $this;
    }

    #[ORM\Column(type: 'integer', nullable: false)]
    private ?int $buyer_id = null;

    public function getBuyer_id(): ?int
    {
        return $this->buyer_id;
    }

    public function setBuyer_id(int $buyer_id): self
    {
        $this->buyer_id = $buyer_id;
        return $this;
    }

    #[ORM\Column(type: 'integer', nullable: false)]
    private ?int $cost = null;

    public function getCost(): ?int
    {
        return $this->cost;
    }

    public function setCost(int $cost): self
    {
        $this->cost = $cost;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $email = null;

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): self
    {
        $this->email = $email;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $firstname = null;

    public function getFirstname(): ?string
    {
        return $this->firstname;
    }

    public function setFirstname(?string $firstname): self
    {
        $this->firstname = $firstname;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $lastname = null;

    public function getLastname(): ?string
    {
        return $this->lastname;
    }

    public function setLastname(?string $lastname): self
    {
        $this->lastname = $lastname;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $note = null;

    public function getNote(): ?string
    {
        return $this->note;
    }

    public function setNote(?string $note): self
    {
        $this->note = $note;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $payment_status = null;

    public function getPayment_status(): ?string
    {
        return $this->payment_status;
    }

    public function setPayment_status(string $payment_status): self
    {
        $this->payment_status = $payment_status;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $phone = null;

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): self
    {
        $this->phone = $phone;
        return $this;
    }

    #[ORM\Column(type: 'integer', nullable: false)]
    private ?int $received_amount = null;

    public function getReceived_amount(): ?int
    {
        return $this->received_amount;
    }

    public function setReceived_amount(int $received_amount): self
    {
        $this->received_amount = $received_amount;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $token = null;

    public function getToken(): ?string
    {
        return $this->token;
    }

    public function setToken(?string $token): self
    {
        $this->token = $token;
        return $this;
    }

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $transaction_id = null;

    public function getTransaction_id(): ?int
    {
        return $this->transaction_id;
    }

    public function setTransaction_id(?int $transaction_id): self
    {
        $this->transaction_id = $transaction_id;
        return $this;
    }

    #[ORM\OneToOne(targetEntity: Payment::class, mappedBy: 'data')]
    private ?Payment $payment = null;

    public function getPayment(): ?Payment
    {
        return $this->payment;
    }

    public function setPayment(?Payment $payment): self
    {
        $this->payment = $payment;
        return $this;
    }

    public function getBuyerId(): ?int
    {
        return $this->buyer_id;
    }

    public function setBuyerId(int $buyer_id): static
    {
        $this->buyer_id = $buyer_id;

        return $this;
    }

    public function getPaymentStatus(): ?string
    {
        return $this->payment_status;
    }

    public function setPaymentStatus(string $payment_status): static
    {
        $this->payment_status = $payment_status;

        return $this;
    }

    public function getReceivedAmount(): ?int
    {
        return $this->received_amount;
    }

    public function setReceivedAmount(int $received_amount): static
    {
        $this->received_amount = $received_amount;

        return $this;
    }

    public function getTransactionId(): ?int
    {
        return $this->transaction_id;
    }

    public function setTransactionId(?int $transaction_id): static
    {
        $this->transaction_id = $transaction_id;

        return $this;
    }

}
