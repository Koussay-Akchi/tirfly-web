<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class DateMinimumOffset extends Constraint
{
    public string $message = 'La date doit être au moins {{ minDays }} jours après aujourd\'hui';
    public int $minDays = 15;

    public function __construct(
        int $minDays = 15,
        string $message = null,
        array $groups = null,
        $payload = null
    ) {
        parent::__construct([], $groups, $payload);

        $this->minDays = $minDays ?? $this->minDays;
        $this->message = $message ?? $this->message;
    }
}