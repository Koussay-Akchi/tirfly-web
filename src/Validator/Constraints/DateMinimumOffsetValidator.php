<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class DateMinimumOffsetValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof DateMinimumOffset) {
            throw new UnexpectedTypeException($constraint, DateMinimumOffset::class);
        }

        if (null === $value) {
            return;
        }

        if (!$value instanceof \DateTimeInterface) {
            throw new UnexpectedValueException($value, '\DateTimeInterface');
        }

        $today = new \DateTime();
        $minDate = (clone $today)->modify("+{$constraint->minDays} days");

        if ($value < $minDate) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ minDays }}', $constraint->minDays)
                ->addViolation();
        }
    }
}