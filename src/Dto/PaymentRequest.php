<?php

namespace App\Dto;

use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\SerializedName;

class PaymentRequest
{
    #[Groups(['payment_request:write'])]
    #[SerializedName('amount')]
    private float $amount;

    #[Groups(['payment_request:write'])]
    #[SerializedName('note')]
    private string $note;

    #[Groups(['payment_request:write'])]
    #[SerializedName('first_name')]
    private string $firstName;

    #[Groups(['payment_request:write'])]
    #[SerializedName('last_name')]
    private string $lastName;

    #[Groups(['payment_request:write'])]
    #[SerializedName('email')]
    private string $email;

    #[Groups(['payment_request:write'])]
    #[SerializedName('phone')]
    private string $phone;

    #[Groups(['payment_request:write'])]
    #[SerializedName('return_url')]
    private string $returnUrl;

    #[Groups(['payment_request:write'])]
    #[SerializedName('cancel_url')]
    private string $cancelUrl;

    #[Groups(['payment_request:write'])]
    #[SerializedName('webhook_url')]
    private string $webhookUrl;

    #[Groups(['payment_request:write'])]
    #[SerializedName('order_id')]
    private string $orderId;

    // Getters and setters
    public function getAmount(): float
    {
        return $this->amount;
    }

    public function setAmount(float $amount): self
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

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): self
    {
        $this->firstName = $firstName;
        return $this;
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): self
    {
        $this->lastName = $lastName;
        return $this;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;
        return $this;
    }

    public function getPhone(): string
    {
        return $this->phone;
    }

    public function setPhone(string $phone): self
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