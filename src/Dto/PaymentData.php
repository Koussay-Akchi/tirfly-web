<?php

namespace App\Dto;

use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\SerializedName;

class PaymentData
{
    #[Groups(['payment_response:read'])]
    #[SerializedName('token')]
    private ?string $token = null;

    #[Groups(['payment_response:read'])]
    #[SerializedName('order_id')]
    private ?string $orderId = null;

    #[Groups(['payment_response:read'])]
    #[SerializedName('first_name')]
    private ?string $firstName = null;

    #[Groups(['payment_response:read'])]
    #[SerializedName('last_name')]
    private ?string $lastName = null;

    #[Groups(['payment_response:read'])]
    #[SerializedName('email')]
    private ?string $email = null;

    #[Groups(['payment_response:read'])]
    #[SerializedName('phone')]
    private ?string $phone = null;

    #[Groups(['payment_response:read'])]
    #[SerializedName('note')]
    private ?string $note = null;

    #[Groups(['payment_response:read'])]
    #[SerializedName('amount')]
    private ?float $amount = null;

    #[Groups(['payment_response:read'])]
    #[SerializedName('payment_url')]
    private ?string $paymentUrl = null;

    #[Groups(['payment_response:read'])]
    #[SerializedName('payment_status')]
    private ?bool $paymentStatus = null;

    public function getToken(): ?string
    {
        return $this->token;
    }

    public function setToken(?string $token): self
    {
        $this->token = $token;
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

    public function getNote(): ?string
    {
        return $this->note;
    }

    public function setNote(?string $note): self
    {
        $this->note = $note;
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

    public function getPaymentStatus(): ?bool
    {
        return $this->paymentStatus;
    }

    public function setPaymentStatus(?bool $paymentStatus): self
    {
        $this->paymentStatus = $paymentStatus;
        return $this;
    }
}