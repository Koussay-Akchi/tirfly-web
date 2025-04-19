<?php

namespace App\Dto;

use Symfony\Component\Serializer\Annotation\Groups;
use Ramsey\Uuid\Uuid;

class PaymentRequest
{
    #[Groups(['payment_request:write'])]
    private ?float $amount = null;

    #[Groups(['payment_request:write'])]
    private string $note = 'Payment for Flight Ticket';

    #[Groups(['payment_request:write'])]
    private ?string $firstName = null;

    #[Groups(['payment_request:write'])]
    private ?string $lastName = null;

    #[Groups(['payment_request:write'])]
    private ?string $email = null;

    #[Groups(['payment_request:write'])]
    private ?string $phone = null;

    #[Groups(['payment_request:write'])]
    private string $returnUrl = 'https://localhost:8000/payment';

    #[Groups(['payment_request:write'])]
    private string $cancelUrl = 'https://localhost:8000/payment';

    #[Groups(['payment_request:write'])]
    private string $webhookUrl = 'https://localhost:8000/admin';

    #[Groups(['payment_request:write'])]
    private string $orderId;

    public function __construct()
    {
        $this->orderId = Uuid::uuid4()->toString();
    }

    public function getAmount(): ?float
    {
        return $this->amount;
    }

    public function setAmount(?float $amount): self
    {
        $this->amount = $amount;
        return $this;
    }

    public function getNote(): string
    {
        return $this->note;
    }

    public function setNote(string $note): self
    {
        $this->note = $note;
        return $this;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(?string $firstName): self
    {
        $this->firstName = $firstName;
        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(?string $lastName): self
    {
        $this->lastName = $lastName;
        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): self
    {
        $this->email = $email;
        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): self
    {
        $this->phone = $phone;
        return $this;
    }

    public function getReturnUrl(): string
    {
        return $this->returnUrl;
    }

    public function setReturnUrl(string $returnUrl): self
    {
        $this->returnUrl = $returnUrl;
        return $this;
    }

    public function getCancelUrl(): string
    {
        return $this->cancelUrl;
    }

    public function setCancelUrl(string $cancelUrl): self
    {
        $this->cancelUrl = $cancelUrl;
        return $this;
    }

    public function getWebhookUrl(): string
    {
        return $this->webhookUrl;
    }

    public function setWebhookUrl(string $webhookUrl): self
    {
        $this->webhookUrl = $webhookUrl;
        return $this;
    }

    public function getOrderId(): string
    {
        return $this->orderId;
    }

    public function setOrderId(string $orderId): self
    {
        $this->orderId = $orderId;
        return $this;
    }
}