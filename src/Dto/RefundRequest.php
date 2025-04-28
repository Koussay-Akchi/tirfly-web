<?php

namespace App\Dto;

use Symfony\Component\Serializer\Annotation\Groups;

class RefundRequest
{
    #[Groups(['refund_request:write'])]
    private ?int $transactionId = null;

    #[Groups(['refund_request:write'])]
    private ?float $amount = null;

    public function getTransactionId(): ?int
    {
        return $this->transactionId;
    }

    public function setTransactionId(?int $transactionId): self
    {
        $this->transactionId = $transactionId;
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
}