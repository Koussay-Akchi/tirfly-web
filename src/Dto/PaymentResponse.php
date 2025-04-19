<?php

namespace App\Dto;

use Symfony\Component\Serializer\Annotation\Groups;

class PaymentResponse
{
    #[Groups(['payment_response:read'])]
    private ?bool $status = null;

    #[Groups(['payment_response:read'])]
    private ?string $message = null;

    #[Groups(['payment_response:read'])]
    private ?int $code = null;

    #[Groups(['payment_response:read'])]
    private ?PaymentData $data = null;

    public function getStatus(): ?bool
    {
        return $this->status;
    }

    public function setStatus(?bool $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(?string $message): self
    {
        $this->message = $message;
        return $this;
    }

    public function getCode(): ?int
    {
        return $this->code;
    }

    public function setCode(?int $code): self
    {
        $this->code = $code;
        return $this;
    }

    public function getData(): ?PaymentData
    {
        return $this->data;
    }

    public function setData(?PaymentData $data): self
    {
        $this->data = $data;
        return $this;
    }
}