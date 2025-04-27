<?php

namespace App\Dto;

use Symfony\Component\Serializer\Annotation\Groups;

class PaymentData
{
    #[Groups(['payment:read', 'payment:write'])]
    private ?string $note = null;

    #[Groups(['payment:read', 'payment:write'])]
    private ?string $phone = null;

    #[Groups(['payment:read', 'payment:write'])]
    private ?string $token = null;

    #[Groups(['payment:read', 'payment:write'])]
    private ?string $lastName = null;

    #[Groups(['payment:read', 'payment:write'])]
    private ?string $bindingId = null;

    #[Groups(['payment:read', 'payment:write'])]
    private ?string $orderId = null;

    #[Groups(['payment:read', 'payment:write'])]
    private ?float $amount = null;

    #[Groups(['payment:read', 'payment:write'])]
    private ?string $paymentUrl = null;

    #[Groups(['payment:read', 'payment:write'])]
    private ?string $firstName = null;

    #[Groups(['payment:read', 'payment:write'])]
    private ?string $email = null;

    public function getNote(): ?string
    {
        return $this->note;
    }

    public function setNote(?string $note): self
    {
        $this->note = $note;
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

    public function getToken(): ?string
    {
        return $this->token;
    }

    public function setToken(?string $token): self
    {
        $this->token = $token;
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

    public function getBindingId(): ?string
    {
        return $this->bindingId;
    }

    public function setBindingId(?string $bindingId): self
    {
        $this->bindingId = $bindingId;
        return $this;
    }

    public function getOrderId(): ?string
    {
        return $this->orderId;
    }

    public function setOrderId(?string $orderId): self
    {
        $this->orderId = $orderId;
        return $this;
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

    public function getPaymentUrl(): ?string
    {
        return $this->paymentUrl;
    }

    public function setPaymentUrl(?string $paymentUrl): self
    {
        $this->paymentUrl = $paymentUrl;
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

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): self
    {
        $this->email = $email;
        return $this;
    }
}