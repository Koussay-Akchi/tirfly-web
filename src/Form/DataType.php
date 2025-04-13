<?php

namespace App\Form;

use App\Entity\Data;
use App\Entity\Payment;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DataType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('amount')
            ->add('buyer_id')
            ->add('cost')
            ->add('email')
            ->add('firstname')
            ->add('lastname')
            ->add('note')
            ->add('payment_status')
            ->add('phone')
            ->add('received_amount')
            ->add('token')
            ->add('transaction_id')
            ->add('payment', EntityType::class, [
                'class' => Payment::class,
'choice_label' => 'id',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Data::class,
        ]);
    }
}
